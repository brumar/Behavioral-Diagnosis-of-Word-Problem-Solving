<?php

// Enum: type d'operations
abstract class DecPol
{
	// when a number appear in a formula, what do we think it comes from ?
	// This class let the program to refer to these different places to check the provenance of a number
	// For example a priority order will be used under the form of an order list of these constants
	
	const  lastComputed=0;
	const  afterEqual=1;
	const  computed=2;
	const  problem=3;
}	

?>