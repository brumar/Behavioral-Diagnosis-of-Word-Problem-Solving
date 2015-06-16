<?php

require_once('class.simplFormul.php');
require_once('enums/enum.regexPatterns.php');
require_once('class.mentalFormul.php');

class	Answer
{
	private	$strbrut;//text brut
	private	$str; // text after some replacements
	private	$nbs;//associative array "23"=>"N1"
	private $numbersInProblem;//"23", etc...
	private $availableMentalNumbers;// numbers that can be computed by a mental operation
	private $availableNumbers;
	private $langage;
	
	private	$full_exp;
	private $simpl_formulas;//formulas as string
	private $simpl_fors; // bind computed numbers to their formula
	private	$simpl_fors_obj; //formulas as object
	private	$interp; //Boolean indicating if the answer as a whole is interpretable
	private $verbose; //string indicating if verbal report or not (to debug)
	
	static $tabReplacements;
	


	public function	Answer($str, $nbs_problem,$verbose=False,$langage="french")
	{
		$this->availableMentalNumbers=[];
		$this->verbose=$verbose;
		$this->interpretable=True;
		$this->strbrut = $str;
		$this->nbs=$nbs_problem;
		$this->numbersInProblem=array_keys($this->nbs);
		$this->availableNumbers=$this->numbersInProblem;
		$this->simpl_fors = [];
		$this->langage=$langage; //TODO an enum would be better
		$this->replaceElementsInAnswer();
		$this->updateAvailableMentalNumbers();
		$this->analyse($nbs_problem);
	}
	
	public function updateAvailableNumbers(){
		/*
		 * Update Number list that have been reached by computation or are defined in the problem
		* */
		$this->availableNumbers=array_merge($this->availableNumbers,array_keys($this->simpl_fors));//TODO
	}
	
	public function updateAvailableMentalNumbers(){
		/*  
		 * Update Number list that can be reached by mental computation
		 * */
		$availableMentalNumbers=[];
		$doublons=perm($this->availableNumbers,2);
		$alreadySeen=$this->availableNumbers; // Numbers already computed or defined in the problem cannot be produced by mental calculation 
		foreach($doublons as $doublon){
			sort($doublon);
			if($doublon[0]!=$doublon[1]){ // 42-42 and 42+42 are avoided
				$n_plus=strval(intval($doublon[1])+intval($doublon[0]));
				if(!in_array($n_plus,$alreadySeen)){
					$availableMentalNumbers[$n_plus]["formula"]=$doublon[1].' + '.$doublon[0];
					$availableMentalNumbers[$n_plus]["str"]=$availableMentalNumbers[$n_plus]["formula"].' = '.$n_plus;
					$alreadySeen[]=$n_plus;
				}
				// TODO: check if not already computed in simple_fors
				$n_moins=strval(intval($doublon[1])-intval($doublon[0]));
				if(!in_array($n_moins,$alreadySeen)){
					$availableMentalNumbers[$n_moins]["formula"]=$doublon[1].' - '.$doublon[0];
					$availableMentalNumbers[$n_moins]["str"]=$availableMentalNumbers[$n_moins]["formula"].' = '.$n_moins;
					$alreadySeen[]=$n_moins;
				}
				
			}
		}
		$this->availableMentalNumbers=$availableMentalNumbers;	
	}


	// Outputs:
	// - formules simples
	function	find_simpl_for()
	{
		preg_match_all(RegexPatterns::completeOperation,
			$this->str, $ar_temp, PREG_SET_ORDER);
		$this->simpl_formulas=[];
		
		foreach($ar_temp as $a){
			$this->simpl_formulas[]=$a[0]; // avoid the fact that with preg_match_all, all elements are at [0]
		}
		
	}

	// Analyses a simple arithmetic problem answer.
	// WORKS ONLY FOR ADDITIONS / SUBSTRACTIONS!
	// NO NEGATIVE NUMBERS ALLOWED!
	public function	analyse($nbs_problem,$verbose=True)
	{
		$this->find_simpl_for();
		$this->sortFormulas();
		$i = 0;
		foreach ($this->simpl_formulas as $simpl_form)
		{
			$nUnkowns=$this->unknownCount($simpl_form);
			if($nUnkowns>1){
				$mentalCalculations=$this->detectMentalCalculations($simpl_form);
				if($nUnkowns-count($mentalCalculations)==1){ // only unknown remains : the result of the operation
					foreach ($mentalCalculations as $mentalCalculation){
						$i=$this->addFormula($i,$mentalCalculation);
					}
				}
				else{
					$this->interpretable=False;
					//too many or not enough mental calculations to understand this operations
				}
			}
			if($this->interpretable==True)
			{
				$formula=new SimplFormul($simpl_form, $nbs_problem, $this->simpl_fors);
				$i=$this->addFormula($i,$formula);
				$this->updateAvailableNumbers();
				$this->updateAvailableMentalNumbers();
			}
		}
	}

	public function findAnswer(){
		
	}
	
	public function addFormula($i,$formula){
		$this->simpl_fors_obj[$i]=$formula;
		if($this->verbose==True){
			$this->simpl_fors_obj[$i]->_print();
		}
		$this->simpl_fors[$this->simpl_fors_obj[$i]->result] = $this->simpl_fors_obj[$i]->formul;
		return $i;			
	}
	
	public function sortFormulas()
	{
		/*//NO SORTING ANYMORE => leads to many problems
		$count=array();
		foreach ($this->simpl_formulas as $simpl_formula)
		{
			$unknownCount=$this->unknownCount($simpl_formula);
			$count[]=$unknownCount;
		}
		array_multisort($count, SORT_ASC,$this->simpl_formulas);*/
	}
	
	public function detectMentalCalculations($formula){
		preg_match_all(RegexPatterns::number, $formula, $nbs);
		$numbersInFormula=$nbs[0];
		$mentalCalculations=[];
		foreach($numbersInFormula as $n){
			if(in_array($n,array_keys($this->availableMentalNumbers))){
				$mentalCalculations[]=new MentalFormul($this->availableMentalNumbers[$n]["str"], $this->nbs,$this->simpl_fors);
			}
		}
		return $mentalCalculations;
	}
	
	public function unknownCount($simpl_formula)
	{
		preg_match_all(RegexPatterns::number, $simpl_formula, $nbs);
		$numbersInFormula=$nbs[0];
		$c=count(array_diff($numbersInFormula,$this->availableNumbers));
		return $c;
	}


	static function initReplacements(){	
		self::$tabReplacements['french']['1']=array(' un ','01');
		self::$tabReplacements['french']['2']=array('deux',' deu ','02');
		self::$tabReplacements['french']['3']=array(' trois ',' troi ','03');  
		self::$tabReplacements['french']['4']=array(' quatre ',' catr ',' quatr ','04');
		self::$tabReplacements['french']['5']=array(' cinq ',' sinq ',' sinc ','05');
		self::$tabReplacements['french']['6']=array(' six ',' sis ',' cis ',' cix ','06');
		self::$tabReplacements['french']['7']=array(' sept ',' cept ','07');
		self::$tabReplacements['french']['8']=array(' huit ',' uit ','08');
		self::$tabReplacements['french']['9']=array(' neuf ',' nef ','09');
		self::$tabReplacements['french']['10']=array(' dix ',' dis ');	
	}
	
	public function replaceElementsInAnswer(){
		$repTable=self::$tabReplacements[$this->langage];
		foreach ($repTable as $index => $patterns)
		{
			$pattern_final='#';
			foreach ($patterns as $pattern)
			{
				$pattern_final=$pattern_final.$pattern.'|';
			}
			$pattern_final = substr($pattern_final,0,strlen($pattern_final)-1);  //permet d'enlever le dernier 'ou' en trop
			$pattern_final=$pattern_final.'#i';
			$tab[$index]=$pattern_final;
			}
			$temp=$this->strbrut;
			foreach ($tab as $index => $pattern)
			{
				$temp=preg_replace( $pattern,$index,$temp);		
			}
			$this->str=$temp;
	}	
	
}

 function perm($arr, $n, $result = array())
{
	if($n <= 0) return false;
	$i = 0;

	$new_result = array();
	foreach($arr as $r) {
		if(count($result) > 0) {
			foreach($result as $res) {
				$new_element = array_merge($res, array($r));
				$new_result[] = $new_element;
			}
		} else {
			$new_result[] = array($r);
		}
	}

	if($n == 1) return $new_result;
	return perm($arr, $n - 1, $new_result);
}
Answer::initReplacements();
?>
