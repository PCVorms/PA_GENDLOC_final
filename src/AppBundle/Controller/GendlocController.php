<?php

// D�finition de GendlocController qui est le controller qui g�re les actions qui lui sont attribu�es dans routing.yml. Ce sont les actions "m�tier" utilis� par l'op�rateur

namespace AppBundle\Controller;

use AppBundle\Entity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class GendlocController extends Controller
{
    /**
     * @Route("/index", name="index")
     * @Security("has_role('ROLE_OPERATOR')")
     */
     // indexAction() est la m�thode qui est appel�e quand on tente de rejoindre la route "index"
    public function indexAction(Request $request)
    {
        //chargement des coordonn�es de l'unit� de l'utilisateur courant dans la variable $rows.
        require __DIR__.'/../Services/coordinates.php';
        
        //redirection vers home.html.twig avec les param�tres lng et lat pour centrer la carte sur l'unit�
        return $this->render('default/home.html.twig', array('lng' => $rows[0]['lng'], 'lat' => $rows[0]['lat']));
    }


    /**
     * @Route("/home", name="home")
     * @Security("has_role('ROLE_OPERATOR')")
     */
    public function homeAction(Request $request)
    {
        //redirection vers home.html.twig
        return $this->render('default/home.html.twig');
    }
    
    /**
     * @Route("/search", name="search")
     * @Security("has_role('ROLE_OPERATOR')")
     */
    public function searchAction(Request $request)
    {
         //chargement des coordonn�es de l'unit� de l'utilisateur courant dans la variable $rows.
        require __DIR__.'/../Services/coordinates.php';
        
        // redirection vers search.html.twig avec les param�tres lng et lat pour centrer la carte sur l'unit�
        return $this->render('default/search.html.twig', array( 'lng'=> $rows[0]['lng'], 'lat'=>$rows[0]['lat'] ));
    }

     /**
     * @Route("/searchResult", name="search_result")
     * @Security("has_role('ROLE_OPERATOR')")
     */
    public function searchResultAction(Request $request)
    {
        // r�cup�ration du terme � rechercher 
        $entree = $_GET['c'];
        
        //url de requete nominatim sur le terme � rechercher
        $request_url = 'http://nominatim.openstreetmap.org/search?q='.$entree.'&format=xml&countrycodes=fr&polygon_geojson=1&addressdetails=1';
        
        $request_params = '<?xml version="1.0" encoding="UTF-8"?>';
        
        //requ�te curl pour acc�der aux information Nominatim
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml', 'Referer: http://pghm-isere.com'));
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        $response = curl_exec( $ch );

        //parser la r�ponse XML
        $res = new \SimpleXMLElement($response);
        
        // redirection vers searchResult.html.twig avec les param�tres term et res
        return $this->render('default/searchResult.html.twig', array('term' => $entree, 'res' => $res ));

    }

     /**
     * @Route("/geoloc", name="geoloc")
     * @Security("has_role('ROLE_OPERATOR')")
     */
    public function geolocAction(Request $request)
    {
        //chargement des coordonn�es de l'unit� de l'utilisateur courant dans la variable $rows.
        require __DIR__.'/../Services/coordinates.php';
        
        // redirection vers geoloc.htlm.twig avec les param�tres lng et lat pour centrer la carte sur l'unit�
        return $this->render('default/geoloc.html.twig', array('lng' => $rows[0]['lng'], 'lat' => $rows[0]['lat']));
    }

    /**
     * @Route("/sms_send", name="sms_send")
     * @Security("has_role('ROLE_OPERATOR')")
     */
    public function sms_sendAction(Request $request)
    {
        //Envoi du sms
        require __DIR__.'/../Services/envoi_sms.php';

        //R�cup�ration de tous les envois de sms de la journ�e
        require __DIR__.'/../Services/day_sms.php';
        
        // redirection vers smsloc.html.twig avec le param�tre data contenant les informations r�cup�r�es par day_sms
        return $this->render('default/smsloc.html.twig', array('data' => $data));
    }

    /**
     * @Route("/maj_sms", name="maj_sms")
     * @Security("has_role('ROLE_OPERATOR')")
     */
    public function maj_smsAction(Request $request)
    {
        //R�cup�ration de tous les envois de sms de la journ�e
        require __DIR__.'/../Services/day_sms.php';
        
        //R�cup�ration de l'altitude
        require __DIR__.'/../Services/open_alti.php';
        
        //R�cup�ration de la commune et du service comp�tent
        require __DIR__.'/../Services/req_commune.php';
        
        // redirection vers smsloc.html.twig avec le param�tre data contenant les informations r�cup�r�es par day_sms, open_alti et req_commune
        return $this->render('default/smsloc.html.twig', array('data' => $data));
    }

     /**
     * @Route("/open_alti", name="open_alti")
     * @Security("has_role('ROLE_OPERATOR')")
     */
    public function open_altiAction(Request $request)
    {
        //R�cup�ration de tous les envois de sms de la journ�e
        require __DIR__.'/../Services/open_alti.php';
        
        // redirection vers smsloc.html.twig avec le param�tre data mis � jour avec les donn�es d'altitude
        return $this->render('default/smsloc.html.twig', array('data' => $data));
    }
    
     /**
     * @Route("/req_commune", name="req_commune")
     * @Security("has_role('ROLE_OPERATOR')")
     */
    public function req_communeAction(Request $request)
    {
        //R�cup�ration de tous les envois de sms de la journ�e
        require __DIR__.'/../Services/req_commune.php';
        
        // redirection vers smsloc.html.twig avec le param�tre data mis � jour avec les donn�es de commune et competence
        return $this->render('default/carte.html.twig', array('data' => $data));
    }
    
}

?>
