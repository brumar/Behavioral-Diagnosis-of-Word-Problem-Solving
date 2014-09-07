<?php

// Enum: type de resolution
abstract class Type_de_Resolution
{
	const	addition_a_trou = 0;
	const	soustraction_simple = 1;
	const	soustraction_inversee = 2;
	const	operation_mentale = 3;

	private function	_construct(){}
}

function	print_tdr($type_de_resolution)
{
	switch($type_de_resolution)
	{
		case Type_de_Resolution::addition :
			echo "addition";
			break;
		case Type_de_Resolution::soustraction :
			echo "soustraction";
			break;
		default :
			echo "(type de resolution non reconnu)";
	}
}

?>
