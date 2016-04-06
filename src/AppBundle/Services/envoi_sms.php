<?php
// Fichier qui effectue l'envoi du SMS

//récupération des valeurs $_GET présentes dans le formulaire de geoloc.html.twig

$msg = ($_GET["message"]);
$lang = ($_GET["lang"]);
$val = ($_GET["val"]);
$tel = ($_GET["tel"]);
$date = date('Y-m-d');
$heure = date('His');

//Récupération de l'Id de l'opérateur courant.
$operator = $this->get('security.token_storage')->getToken()->getUser();
$operator_id = $operator->getId();
$unite_id = $operator->getUnite();

//connection db
$conn = $this->get('database_connection');

//récupération de l'unité de l'opérateur courant
$query = "SELECT name FROM app_unites where id=".$unite_id;
$rows =$conn->fetchAll($query);
$unite = $rows[0]['name'];


//génération du token aléatoire.
$bytes = random_bytes(5);
$token = bin2hex($bytes);

//définition du statut du sms
$statut = "ENVOI";

//Création d'un objet Smsloc
$sms_object = new \AppBundle\Entity\Smsloc();
$sms_object->setOperatorId($operator_id);
$sms_object->setSmsToken($token);
$sms_object->setStatut($statut);
$sms_object->setTelNumber($tel);
$sms_object->setDate($date);
$sms_object->setHeure($heure);
$sms_object->setValidite($val);

//Insertion d'une ligne dans smsloc
$em = $this->getDoctrine()->getManager();
$em->persist($sms_object);
$em->flush();

//Récupération de l'id du sms
//$lastid = (string)$sms_object->getId();
//$lastid = $token."&h=".$heure;
$lastid = $heure.$token;


print_r($unite);

//gÃ©nÃ©ration texte suivant msg langue et code
if ($unite=="CROSS") {
	$sender = "SECOURSMER";
	if ($msg=="Geoloc")
	{
		$text = "SECOURS EN MER\r\n* VÃ©rifier GPS et DATA Ã  ON\r\n* http://pghm-isere.com/gendloc/position?c=".$lastid."\r\n* Accepter le partage\r\n* Patienter\r\n".$unite;
	}
	elseif ($msg=="Tracking")
	{
		$text = "SECOURS EN MER\r\n* VÃ©rifier GPS et DATA Ã  ON\r\n* http://pghm-isere.com/gendloc/trace.php?c=".$lastid."\r\n* Accepter le partage\r\n".$unite;
	}
}
elseif ($unite=="SDSI")
{
    $sender = "SECOURSMER";
	if ($msg=="Geoloc")
	{
		$text = "SECOURS EN MER\r\n* VÃ©rifier GPS et DATA Ã  ON\r\n* http://pghm-isere.com/gendloc/position?c=".$lastid."\r\n* Accepter le partage\r\n* Patienter\r\n".$unite;
	}
	elseif ($msg=="Tracking")
	{
		$text = "SECOURS EN MER\r\n* VÃ©rifier GPS et DATA Ã  ON\r\n* http://pghm-isere.com/gendloc/trace.php?c=".$lastid."\r\n* Accepter le partage\r\n".$unite;
    }
}
else
{
   $sender = "SECOURSMONT";
   if ($msg=="Geoloc")
   {
       $text = sprintf(
           "SECOURS EN MONTAGNE\r\n*" .
           " Vérifier GPS et DATA à ON\r\n".
           "\r\n* Accepter le partage\r\n" .
           "* Patienter\r\n* http://%s:%s%s %s",
           $request->getHost(),
           $request->getPort(),
           $this->generateUrl('position', array('c' => $lastid), true),
           $unite);
	}
   elseif ($msg=="Tracking")
   {
        $text = "SECOURS EN MONTAGNE\r\n* VÃ©rifier GPS et DATA Ã  ON\r\n* http://pghm-isere.com/gendloc/trace.php?c=".$lastid."\r\n* Accepter le partage\r\n".$unite;
    }
	elseif ($msg=="APN")
	{
        $text = "SECOURS EN MONTAGNE\r\n* VÃ©rifier GPS et DATA Ã  ON\r\n* http://pghm-isere.com/gendloc/apn.php?c=".$lastid."\r\n".$unite;
    }
	elseif ($msg=="APNV")
	{
        $text = "SECOURS EN MONTAGNE\r\n* VÃ©rifier GPS et DATA Ã  ON\r\n* http://pghm-isere.com/gendloc/apnv.php?c=".$lastid."\r\n".$unite;
    }
}	

// Création d'un bouchon pour dev
if ($this->container->getParameter('kernel.environment') == 'dev') {
    $message = \Swift_Message::newInstance()
             ->setSubject('[DEV] SMS from gendloc')
             ->setFrom('dev@gendloc.gend')
             ->setTo($this->container->getParameter('devmail'))
             ->setBody(
                     $this->renderView(
                         'email/debug.txt.twig',
                         array('text' => $text)
                     ),
                     'text/plain'
             );
    $this->get('mailer')->send($message);
} else {
    //envoi SMS et récupération du hash d'envoi
    require __DIR__.'/../../../web/js/thecallR/src/ThecallrClient.php';
    $thecallrLogin = $this->container->getParameter('thecallrLogin');
    $thecallrPassword = $this->container->getParameter('thecallrPassword');
    $THECALLR = new ThecallrClient($thecallrLogin, $thecallrPassword);
    $settings = json_decode(
        '{
              "push_mo_enabled": true,
              "push_mo_url": "http://pghm-isere.com/gendloc/sms/reception.php",
              "push_dlr_enabled": true,
              "push_dlr_url": "http://pghm-isere.com/gendloc/sms/accuse.php"
        }'
    );
    $res=$THECALLR->send('sms.set_settings', array($settings));
    try {
        // Options
        $options = new stdClass();
        $options->flash_message = FALSE;
        $options->user_data = $lastid;
        // "sms.send" method execution
        $result = $THECALLR->call('sms.send', $sender, $tel, $text, $options);
    } catch (Exception $error) {
        die($error->getMessage());
        $arr = array('success' => false, 'data' => "Vérifier");
    }
}
?>
