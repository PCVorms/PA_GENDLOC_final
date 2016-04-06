<?php
// Définition du ReceptionController qui gère les actions du côté du requérant

namespace AppBundle\Controller;

use AppBundle\Entity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class ReceptionController extends Controller
{
    /**
     * @Route("/position", name="position")
     */
    public function positionAction(Request $request)
    {
        // récupération du token enregistré dans la variable c
        $token = $_GET['c'];
        // redirection vers position.html.twig avec le paramètre token
        return $this->render('default/position.html.twig', array('token' => $token));
    }

    /**
     * @Route("/position/succes", name="succes")
     */
    public function succesAction(Request $request)
    {
        $time_token = $_GET['l'];
        if ($this->container->getParameter('kernel.environment') == 'dev') {
            $lng = '2.271';
            $lat = '48.815';
        } else {
            $lng = $_GET['lng'];
            $lat = $_GET['lat'];
        }
        if ($this->container->getParameter('kernel.environment') == 'dev') {
            $precision = 30;
        } else {
            $precision = $_GET['prec'];
        }
        $n = $_GET['n'];
        $agent=$_SERVER['HTTP_USER_AGENT'];
        $heure = substr($time_token, 0, 6);
        $token = substr($time_token, 6);

        //connection bdd
        $conn = $this->get('database_connection');

        //Récupération du SMS_id correspondant au token
        $query = "SELECT id,operator_id,tel_number,date,heure,validite FROM app_smsloc WHERE sms_token='".$token."' AND heure='".$heure."'";
        $rows = $conn->fetchAll($query);

        //vérification du nombre de sms correspondant
        $rows_number = count($rows, 0);
        if ($rows_number != 1) {
                return new Response('<html><body>ERREUR : JETON INVALIDE</body></html>');
        }

        // récupération des données issues de la requête
        $sms_id = $rows[0]['id'];
        $date = $rows[0]['date'];
        $heure = $rows[0]['heure'];
        $operator_id = $rows[0]['operator_id'];
        $tel = $rows[0]['tel_number'];
        $val = $rows[0]['validite'];
        // ERREUR PERMISSION GPS
        if ($n==4) {
                $query = "UPDATE app_smsloc SET statut = 'PERMISSION' WHERE id =".$sms_id;
                $result = $conn->executeUpdate($query);
                return new Response("<html><body>VOUS DEVEZ AUTORISER LE PARTAGE DE VOTRE POSITION !</body></html>");
        }

        // ERREUR GPS
        if ($n==5 and $this->container->getParameter('kernel.environment') != 'dev') {
                $quer = "UPDATE app_smsloc SET statut = 'ERREUR GPS' WHERE id =".$sms_id;
                $result = $conn->executeUpdate($query);
                return new Response("<html><body>GPS allumé à l'extérieur !</body></html>");
        }

        // ERREUR TIMEOUT
        if ($n==6) {
                $quer = "UPDATE app_smsloc SET statut = 'TIME_OUT' WHERE id =".$sms_id;
                $result = $conn->executeUpdate($query);
                return new Response("<html><body>GPS allumé à l'extérieur !</body></html>");
        }

        // Récupération de l'unité de l'opérateur à l'origine du SMS 
        $query = "SELECT app_unites.id AS id, name from app_unites, app_users WHERE app_users.unite=app_unites.id AND app_users.id=".$operator_id;
        $row = $conn->fetchAll($query);
        $unite_id = $row[0]['id'];
        $unite = $row[0]['name'];

        //Récupération de l'heure actuelle et comparaison à la validité
        $str_sending_date = $date." ".substr($heure, 0, 2).":".substr($heure, 2, 2).":".substr($heure, 4);
        $format = "Y-m-d - His";
        $sms_sending_date = new \Datetime($str_sending_date);
        $now = new \DateTime();

        $test_val = true;
        switch($val)
        {
            //validité de 2 heures
        case 1 :
            $sms_sending_date->Modify('+2 hours');
            if ($now > $sms_sending_date) {
                $test_val = false;
            }
            break;
        case 2: // validité de 24 heures
            $sms_sending_date->Modify('+1 day');
            if ($now > $sms_sending_date) {
                        $test_val = false;
            }
            break;
        case 3: // validité permanente
            break;
        default:
            $test_val = false;
        }

        // si le test est faux, le lien est expiré
        if (!$test_val) {
            //mise à jour du statut du SMS dans la base de données.
            $query = "UPDATE app_smsloc SET statut = 'LIEN EXPIRE' WHERE id =".$sms_id;
            $result = $conn->executeUpdate($query);
            return new Response("<html><body>Lien expir&eacute...</body></html>");
        }

        //Chargement des fonctions de conversion des coordonnées
        include __DIR__.'/../Services/coord.inc.php';

        //-- Mise à jour du statut du sms dans la table smsloc
        $query = "UPDATE app_smsloc SET statut = 'OK' WHERE id =".$sms_id;
        $result = $conn->executeUpdate($query);

        //-- Insertion dans la table geoloc des données de géolocalisation
        $current_day = $now->format('Y-m-d');
        $query = "INSERT INTO app_geoloc (date, sms_id, useragent, coordinates, prec) 
                  VALUES('$current_day', '$sms_id', '$agent', ST_GeomFromText('POINT($lng $lat)', 4326), '$precision')";
        $result = $conn->query($query);

        // redirection vers succes.html.twig avec tous les paramètres qui lui sont nécessaires
        return $this->render(
            'default/succes.html.twig',
            array('token' => $token,
                  'lat' => $lat,
                  'lng' => $lng,
                  'prec' => $precision,
                  'n' => $n,
                  'agent' => $agent,
                  'heure' => $heure,
                  'sms_id' => $sms_id,
                  'unite' => $unite,
                  'date' => $str_sending_date)
        );
    }
}

?>