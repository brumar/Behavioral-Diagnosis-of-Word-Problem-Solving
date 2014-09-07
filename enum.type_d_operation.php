<?php

// Enum: type d'operations
abstract class Type_d_Operation
{
	const	addition = 0;
	const	soustraction = 1;

	private function	_construct(){}
}

function	print_tdo($type_d_operation)
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

?>
