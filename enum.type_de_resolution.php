<?php

// Enum: type de resolution
abstract class Type_de_Resolution
{
	const	addition_simple = 0;
	const	addition_a_trou = 1;
	const	soustraction_simple = 2;
	const	soustraction_a_trou = 3;
	const	operation_mentale = 4;

	private function	_construct(){}
}

function	print_tdr($type_de_resolution)
{
	switch($type_de_resolution)
	{
		case Type_de_Resolution::addition_simple :
			echo "addition simple";
			break;
		case Type_de_Resolution::addition_a_trou :
			echo "addition a trou";
			break;
		case Type_de_Resolution::addition_a_trou :
			echo "addition a trou";
			break;
		case Type_de_Resolution::soustraction_a_trou :
			echo "soustraction a trou";
			break;
		default :
			echo "(type de resolution non reconnu)";
	}
}

?>
