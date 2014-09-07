<?php

// Enum: type d'operations
abstract class Type_d_Operation
{
	const	addition = 0;
	const	soustraction = 1;

	private function	_construct(){}

	static function	print_tdo($type_d_operation)
	{
		switch($type_d_operation)
		{
			case Type_d_Operation::addition :
				echo "addition";
				break;
			case Type_d_Operation::soustraction :
				echo "soustraction";
				break;
			default :
				echo "(type d'operation non reconnu)";
		}
	}
}

// Outputs in navigator an analysis of
// a simple arithmetic problem answer.
// WORKS ONLY FOR ADDITIONS / SOUSTRACTIONS!
// NO NEGATIVE NUMBERS ALLOWED!
function	f($reponse, $nbs_ennonce)
{
	echo "Nombres de l'ennonce :<br />";
	print_r($nbs_ennonce);
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
		$type_d_operation = f2_1($formule_simple[0]);
		echo "Type d'operation : ";
		Type_d_Operation::print_tdo($type_d_operation);
		echo "<br />";
		// Calculation error
		preg_match_all("/\d+/", $formule_simple[0], $nbs_reponse);
		$calcul_error = f2_2($nbs_reponse, $type_d_operation);
		if ($calcul_error === TRUE)
			echo "Contient une erreur de calcul.<br />";
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
			if ((int)$nbs_reponse[0][0] + (int)$nbs_reponse[0][1]
				=== (int)$nbs_reponse[0][2])
				return (FALSE);
			else
				return (TRUE);
		case 'soustraction' :
			if ((int)$nbs_reponse[0][0] - (int)$nbs_reponse[0][1]
				=== (int)$nbs_reponse[0][2])
				return (FALSE);
			else
				return (TRUE);
	}
}

// Outputs:
// - resolution type as in enum Type_d_Resolution
function	f2_3($nbs_ennonce, )
{
}




?>
