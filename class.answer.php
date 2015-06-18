<?php

require_once('class.simplFormul.php');
require_once('enums/enum.regexPatterns.php');
require_once('class.mentalFormul.php');
require_once('logger/Logger.php');
Logger::configure('configLogger.xml');// Tell log4php to use our configuration file.


class	Answer
{
	private	$strbrut;//text brut
	private	$str; // text after some replacements
	private	$nbs;//associative array "23"=>"N1"
	private $numbersInProblem;//"23", etc...
	private $availableMentalNumbers;// numbers that can be computed by a mental operation
	private $availableNumbers;
	private $langage;
	private $logger;//log the messages
	
	private	$full_exp;
	private $simpl_formulas;//formulas as string
	private $simpl_fors; // bind computed numbers to their formula
	private	$simpl_fors_obj; //formulas as object
	private	$interp; //Boolean indicating if the answer as a whole is interpretable
	private $verbose; //string indicating if verbal report or not (to debug)
	private $finalAnswer="";//final answer given by the subject
	
	static $tabReplacements;
	


	public function	Answer($str, $nbs_problem,$verbose=False,$langage="french",$id="noID")
	{
		$this->logger = Logger::getLogger("main");
		$this->availableMentalNumbers=[];
		$this->verbose=$verbose;
		$this->interpretable=True;
		$this->strbrut = $str;
		$this->nbs=$nbs_problem;
		$this->numbersInProblem=array_keys($this->nbs);
		$this->availableNumbers=$this->numbersInProblem;
		$this->simpl_fors = [];
		
		$this->langage=$langage; //TODO an enum would be better
		$this->loginit ($id);//$id is for log only (in order to ease browsing)
		$this->replaceElementsInAnswer();
		$this->updateAvailableMentalNumbers();
		$this->analyse($nbs_problem);
	}
	
	public function loginit($id) {
		$this->logger->info("******NOUVELLE ANALYSE*******");
		$this->logger->info("******ID=$id*******");
		$date=date("D M H:i");
		$this->logger->info("analyse de : $this->strbrut, language: $this->langage, $date");
		$this->logger->info("nombres :  ");
		$this->logger->info($this->numbersInProblem);
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
		$this->logger->info("updating mentally available numbers");
		$this->logger->info("this is done on the basis of direct availables numbers");
		$this->logger->info($this->availableNumbers);
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
		$this->logger->info("availableMentalNumbers :");
		$this->logger->info(array_keys($availableMentalNumbers));
		$this->logger->trace("availableMentalNumbers : détails");
		$this->logger->trace($availableMentalNumbers);
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
		$this->logger->info("formulas detected : ");
		$this->logger->info($this->simpl_formulas);
		
	}

	// Analyses a simple arithmetic problem answer.
	// WORKS ONLY FOR ADDITIONS / SUBSTRACTIONS!
	// NO NEGATIVE NUMBERS ALLOWED!
	public function	analyse($nbs_problem,$verbose=True)
	{
		$this->preAnalyse ();

		$i = 0; //TODO it's possible to get rid of $i at the condition to be careful that addFormula work well
		foreach ($this->simpl_formulas as $s=>$simpl_form)
		{
			$formlulaIsInterpretable=True;
			$nUnkowns=$this->unknownCount($simpl_form);
			$this->logger->info("analyse of this formula : $simpl_form");
			$this->logger->info("number of unkwowns numbers in the formula (1 is the simplest case) ");
			$this->logger->info($nUnkowns);
			if($nUnkowns>1){
				$this->logger->info("we try to detect mental calculations");
				$mentalCalculations=$this->detectMentalCalculations($simpl_form);
				$nRealUnknowns=$nUnkowns-count($mentalCalculations);
				$this->logger->info("After the mental computation investigation, we count the number of remaining unkwowns (1 is the simplest case) ");
				$this->logger->info($nRealUnknowns);
				switch ($nRealUnknowns)
				{
					case 1 :
						foreach ($mentalCalculations as $mentalCalculation){
							$i=$this->addFormula($i,$mentalCalculation);
						}
						break;
						
					case 0 :
						$this->logger->info("We try to drop a mental formula");
						$next_form = (isset($this->simpl_formulas[$s+1])) ? $this->simpl_formulas[$s+1] : "";
						$mentalCalculations=$this->dropLeastProbableMentalCalculations($mentalCalculations,$simpl_form,$next_form);
						foreach ($mentalCalculations as $mentalCalculation){
							$i=$this->addFormula($i,$mentalCalculation);
						}
					break;		
					
					default:
						$formlulaIsInterpretable=False;
						$this->info("interpretation process of the current formula has failed at this point");
						break;
					//too many or not enough mental calculations to understand this operations
				}
			}
			if($formlulaIsInterpretable==True)
			{
				$formula=new SimplFormul($simpl_form, $nbs_problem, $this->simpl_fors,$this->logger);
				$i=$this->addFormula($i,$formula);
				$this->updateAvailableNumbers();
				$this->updateAvailableMentalNumbers();
			}
		}
	}
	
	public function dropLeastProbableMentalCalculations($listOfMentalCalculations,$simpl_form,$next_form){
		if($next_form=="") 
		// if the formula studied is the last formula, check if one number is given as an answer
			{
			foreach($listOfMentalCalculations as $i=>$mcal){
				if($mcal->result==$this->finalAnswer){
					$this->logger->info("We drop this mental computation because it's the final answer given by the student");
					$this->logger->info($listOfMentalCalculations[$i]["str"]);
					unset($listOfMentalCalculations[$i]);
					return $listOfMentalCalculations;
				}
			}
		}
		else{
		//if one formula come after, check if one number is given as an answer
			preg_match_all(RegexPatterns::number, $next_form, $nbs);
			$numbersInFormula=$nbs[0];		
			foreach($listOfMentalCalculations as $i=>$mcal){
				foreach($numbersInFormula as $nb){
					if($mcal->result==$nb){
						$this->logger->info("We drop this mental computation because the number is reused later by the student");
						$this->logger->info($listOfMentalCalculations[$i]["str"]);
						unset($listOfMentalCalculations[$i]);
						return $listOfMentalCalculations;
					}
				}
			}
		}	
		// last case : consider that the number after the equal is the one genuinely computed
		// but it seems to never be usefull, which is cool
		preg_match_all(RegexPatterns::lastNumberInFormula, $simpl_form, $n);
		$lastNumber=$n[1];
		foreach($listOfMentalCalculations as $j=>$mcal){
			if($mcal->result==$lastNumber){
				$this->logger->info("We drop the mental computation for the number after the equal (no better option)");
				$this->logger->info($listOfMentalCalculations[$j]["str"]);
				unset($listOfMentalCalculations[$j]);
				return $listOfMentalCalculations;
			}			
		}
		$this->logger->error("no mental computation has been droped, this is unexpected");
	}
	
	public function preAnalyse() {
		$this->findFinalAnswer();
		$this->find_simpl_for();
		$this->sortFormulas();
	}


	public function findFinalAnswer(){
		if(preg_match(RegexPatterns::EndresultAfterFormulas,$this->str, $match)==1){
			$this->finalAnswer=$match[1];
			$this->logger->info("final answer  found :");
			$this->logger->info($this->finalAnswer);
		}
		else{
		$this->logger->info("final answer not found");
		}	
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
				$formstr=$this->availableMentalNumbers[$n]["str"];
				$this->logger->info("possible mental formula found : $formstr");
				$mentalCalculations[]=new MentalFormul($this->availableMentalNumbers[$n]["str"], $this->nbs,$this->simpl_fors,$this->logger);
				$this->logger->info("mental calculation suggested : ");
				$this->logger->info($this->availableMentalNumbers[$n]["str"]);
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
		self::$tabReplacements['french']['CM_a_']=array('CM1');
		self::$tabReplacements['french']['CM_b_']=array('CM2');
		self::$tabReplacements['french']['CE_a_']=array('CE1');
		self::$tabReplacements['french']['CE_b_']=array('CE2');
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
			$this->logger->info("answer after replacements :  $this->str");
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
