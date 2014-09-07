<?php

require_once('enum.type_d_operation.php');
require_once('enum.type_de_resolution.php');

// Outputs in navigator an analysis of
// a simple arithmetic problem answer.
// WORKS ONLY FOR ADDITIONS / SOUSTRACTIONS!
// NO NEGATIVE NUMBERS ALLOWED!
function	f($reponse, $nbs_ennonce)
{
	echo "Nombres de l'ennonce :$nbs_ennonce<br />";
	echo "<br />";
	echo "Reponse fournie : \"$reponse\"<br />";
	$formules_simples = f1($reponse);
	echo "<br />";
	echo "Formule(s) simple(s) detectee(s) : <br />";
	print_r($formules_simples);
	echo "<br />";
	echo "<br />";
	foreach ($formules_simples as $formule_simple)
	{
		echo "Formule : $formule_simple[0]<br />";
		// Operation type
		echo "Type d'operation : ";
		$type_d_operation = f2_1($formule_simple[0]);
		print_tdo($type_d_operation);
		echo "<br />";
		// Calculation error
		preg_match_all("/\d+/", $formule_simple[0], $nbs_reponse);
		$calcul_error = f2_2($nbs_reponse[0], $type_d_operation);
		if ($calcul_error === TRUE)
			echo "Contient une erreur de calcul.<br />";
		// Resolution type
		$type_de_resolution = f2_3($nbs_ennonce, $nbs_reponse[0]);
		echo "Type de resolution : ";
		print_tdr($type_de_resolution);
		echo "<br />";
		echo "<br />";
	}
}

// Outputs:
// - formules simples
function	f1($reponse)
{
	preg_match_all("/\d+\s*[+*-\/]\s*\d+\s*=\s*\d+/",
		$reponse, $formules_simples, PREG_SET_ORDER);
	return $formules_simples;
}

// Outputs;
// - type d'operation as in enum Type_d_Operation
function	f2_1($formule_simple)
{
	if (strstr($formule_simple, "+") !== FALSE)
		return Type_d_Operation::addition;
	if (strstr($formule_simple, "-") !== FALSE)
		return Type_d_Operation::soustraction;
	return -1;
}

// Outputs:
// - calcul_error = TRUE/FALSE
function	f2_2($nbs_reponse, $type_d_operation)
{ 
	switch($type_d_operation)
	{
		case 'addition' :
			if ((int)$nbs_reponse[0] + (int)$nbs_reponse[1]
				=== (int)$nbs_reponse[2])
				return (FALSE);
			else
				return (TRUE);
		case 'soustraction' :
			if ((int)$nbs_reponse[0] - (int)$nbs_reponse[1]
				=== (int)$nbs_reponse[2])
				return (FALSE);
			else
				return (TRUE);
	}
}

// Outputs:
// - resolution type as in enum Type_d_Resolution
// Trick : $nbs_ennonce a la forme " x, y, z,"
// pour faciliter la reconnaissance des nombres
// et ne pas confondre 4 et 45 par exemple.
function	f2_3(&$nbs_ennonce, $nbs_reponse)
{
	if (strstr($nbs_ennonce, " ".$nbs_reponse[0].",") !== FALSE
		&& strstr($nbs_ennonce, " ".$nbs_reponse[1].",") !== FALSE)
	{
		$nbs_ennonce .= $nbs_reponse[2];
		return Type_de_Resolution::operation_simple;
	}
	$nbs_ennonce .= " ".$nbs_reponse[0].",";
	$nbs_ennonce .= " ".$nbs_reponse[1].",";
	return Type_de_Resolution::operation_a_trou;
}

?>
