<?php

// This is a class for experimental purpose only
// The researcher is supposed to manipulate these variables at this place only
// Keep Sargs_value::keep by default

abstract class Sargs
{
	//operations
	
	//full = 88%
	const  inferMentalCalculation=Sargs_value::keep; //suspend=>79.33 %
	const  manipulateStringBefore=Sargs_value::keep; //suspend=>82.75 %
	
	//hard decisions
	const  reduceMentalCalculations=Sargs_value::keep; //suspend=> 77.09%, random=88.19% !!
	const  dropLeastMentalCalculation=Sargs_value::keep; // suspend=>  81.78%
	const  backtrackPolicy=Sargs_value::keep;  // 86.2%
	//suspend not implemented because it does not make sense in the context

}	

abstract class Sargs_value
{

	const  suspend=0;
	const  keep=1;
	const  random=2;

}

?>