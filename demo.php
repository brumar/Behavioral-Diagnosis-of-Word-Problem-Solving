<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Interface de Test</title>
<link rel="stylesheet" type="text/css" href="view.css" media="all">
<script type="text/javascript" src="view.js"></script>
<script type="text/javascript" src="userscript.js"></script>

</head>
<body id="main_body" >

<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

require_once('simple_analysis.php');
require_once('class.answer.php');

//echo "Hell yeah!<br />";

$nbs_problem = ["43"=>"N1", "46"=>"N2", "4"=>"N3"];
$str = "46 - 43 = 3 fleurs 4 - 3 = 2 ; Cette reponse n'a aucun sens. 43 + 2 = 47";//100 + 100 = 200 200 - 300 = 200";

echo "<u>Exemple de probleme :<br /></u>Les nombres de l'ennonce sont :<br />";
print_r($nbs_problem);
echo "<u><br /><br />Exemple de reponse d'eleve : </u><br /><i>$str</i><br /><br />";

echo "<u>Deroule de l'analyse (qui aboutit a l'expression complete de la reponse) :<br /></u>";
$coucou = new Answer($str, $nbs_problem);

echo "<u>Evolutions possibles :</u><br />- Prediction de profondeur 1 pour les valeurs calculees de tete<br />- Formules imbriquees (ex 1 + 2 = 3 + 4 = 7)<br />";



?>
</body>
