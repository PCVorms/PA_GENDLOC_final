<?php
// Fichier qui permet de r�cup�rer les informations des SMS envoy�s par l'unit� dans la journ�e

$current_day = date('Y-m-d');
//$current_day = date('2016-03-21'); //DEBUG
$unite = $this->get('security.token_storage')->getToken()->getUser()->getUnite();

//r�cup�ration de tous les envois de sms de la journ�e
// connection � la base de donn�es
$conn = $this->get('database_connection');
// requ�te pour r�cup�rer les informations n�cessaire
$query ="SELECT tel_number, heure, app_smsloc.date, app_geoloc.date AS geoloc_date, ST_Y(coordinates) AS lat, ST_X(coordinates) AS lng, statut 
         FROM app_smsloc
         LEFT JOIN app_geoloc ON app_geoloc.sms_id=app_smsloc.id 
         LEFT JOIN app_users ON app_users.id=app_smsloc.operator_id
         WHERE app_users.unite=".$unite." and app_smsloc.date='".$current_day."'
         ORDER BY app_smsloc.heure DESC";
// envoi de la requ�te et �criture du r�sultat dans $data
$data = $conn->fetchAll($query);

// Mise en forme de l'heure en h:m:s dans $data
$size=count($data);
for($i=0;$i<$size;$i++)
{
    $h=substr($data[$i]['heure'],0,2);
    $m=substr($data[$i]['heure'],2,2);
    $s=substr($data[$i]['heure'],4,2);
    $data[$i]['heure']="$h:$m:$s";
}

// $data est un tableau qui est envoyer � smsloc.html.twig par le contr�leur GendlocController (m�thodes sms_sendAction ou maj_smsAction)


?>