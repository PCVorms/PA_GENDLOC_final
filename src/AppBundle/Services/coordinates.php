<?php
// Fichier qui permet de r�cup�rer les coordonn�es de l'unit� de l'utilisateur connect�

$user = $this->get('security.token_storage')->getToken()->getUser();
$unite = $user->getUnite();

//chargement des coordonn�es de l'op�rateur connect�.
$conn = $this->get('database_connection');
$query = "SELECT ST_X(coordinates::geometry) AS lng, ST_Y(coordinates::geometry) AS lat FROM app_unites where id=".$unite;
$rows =$conn->fetchAll($query);


?>