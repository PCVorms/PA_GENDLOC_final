<?php
// Fichier qui permet de récupérer les données commune et compétence dans les tables

// boucle sur l'ensemble des SMS du jour présent dans le tableau $data de taille $size défini dans day_sms.php
for ($i=0; $i<$size; $i++) {
    if (!$this->container->getParameter('kernel.environment')=='dev') {
        if (isset($data[$i]['lat'])) {
            // récupération des données de latitude et de longitude de la ligne i
            $lat=$data[$i]['lat'];
            $long=$data[$i]['lng'];

            // connection à la base de données
            $conn = $this->get('database_connection');

            // définition du point à étudier
            $loc ="POINT(".$long." ".$lat.")";

            // requête de recherche dans les tables pour récupérer la commune et l'unité compétente
            $quer ="SELECT app_commune.commune, service FROM app_commune INNER JOIN app_competence ON app_commune.ref_insee = app_competence.insee WHERE ST_Contains(geom,ST_GeometryFromText('$loc',4326))";

            // Récupération du résultat de la requête
            $com_bp = $conn->fetchAll($quer);

            // ajout des données dans data
            $data[$i]['commune'] = $com_bp[0]['commune'];
            $data[$i]['service'] = $com_bp[0]['service'];

            // si la latitude n'est pas définie, on ajoute juste les champs commune et service pour qu'il n'y ait pas de bug dans les .twig
        } else {
            $data[$i]['commune'] = "";
            $data[$i]['service'] = "";
        } // end if
    }else{
        $data[$i]['commune'] = "";
        $data[$i]['service'] = "";
    }
} // end for

?>