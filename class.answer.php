<?php

require_once('class.simplFormul.php');
require_once('enums/enum.regexPatterns.php');
require_once('enums/enum.decision_policy.php');//name : DecPol
require_once('class.mentalFormul.php');
require_once('class.evalmath.php');
require_once('class.anomalyManager.php');
require_once('logger/Logger.php');
Logger::configure('../configLogger.xml');// Tell log4php to use our configuration file.


class	Answer
{
	public $policy;//indicates who numbers are infered where they come from
	//3 fields added to support policy mecanisms
	public $lastElementAfterEqualSign;
	public $currentLastFormObj;
	public $lastElementComputed;
	public $anomalyManager;
	//public $computedNumbers; not necessary as its the diff of numbers in pbm and $availableNumbers
	
	private	$strbrut;//text brut 
	private	$str; // text after some replacements --réponse réparée
	private	$nbs;//associative array "23"=>"N1" 
	private $numbersInProblem;//"23", etc...
	private $availableMentalNumbers;// numbers that can be computed by a mental operation
	private $availableNumbers;
	private $langage;
	private $logger;//log the messages
	private $eval;// php math evaluator
	
	private	$full_exp;
	private $simpl_formulas;//formulas as string
	private $formulaCount;// count the number of detected formulas --Nombre de formules
	private $simpl_fors; // bind computed numbers to their formula
	public	$simpl_fors_obj; //formulas as object
	private	$lastFormula; //the formula as object that compute the answer
	private $verbose; //string indicating if verbal report or not (to debug)
	private $finalAnswer="";//final answer given by the subject
	public $finalFormula="";//final formula representing the whole solving process
	private $reverseIndexLastFormula; //reversed index of the formula giving the result --indexFormuleFinale
	private $id;
	private $ininterpretable=False; // boolean indicated if the answer is ininterpretaable --interprétable?
	private $voidAnswer=False;
	public $anomalies=[];
	

	

	
	static $tabReplacements;
	


	public function	Answer($str, $nbs_problem,$verbose=False,$langage="french",$id="noID",
			$policy=[DecPol::lastComputed,DecPol::afterEqual,DecPol::computed,DecPol::problem])
	//priorityList=["LastComputed","afterEqual","computed,"problem"]
	{
		$this->anomalyManager=new AnomaliesManager();
		$this->eval= new EvalMath;
		$this->logger = Logger::getLogger("main");
		$this->availableMentalNumbers=[];
		$this->verbose=$verbose;
		$this->strbrut = $str;
		$this->nbs=$nbs_problem;
		$this->numbersInProblem=array_keys($this->nbs);
		$this->availableNumbers=$this->numbersInProblem;
		$this->simpl_fors = [];
		$this->simpl_fors_obj=[];
		$this->id=$id;
		$this->policy=$policy;
		
		$this->langage=$langage; //TODO an enum would be better
		$this->process();// TODO should be commented out one day

	}
	/**
	 * 
	 */public function process() {
		$this->loginit ($this->id);//$id is for log only (in order to ease browsing)
		$this->replaceElementsInAnswer();
		$this->repairSpecialFormulas();
		$this->updateAvailableMentalNumbers();
		$this->findFinalAnswer();
		$this->find_simpl_for();
		$this->sortFormulas();
		$this->sequentialAnalysis($this->nbs); // most important line
		$this->globalAnalysis();
		$this->getAnomalies();
		$this->printSummary();
	}

	
	public function printSummary(){
		if($this->verbose==True){
			foreach($this->simpl_fors_obj as $form)
			{
				$form->_print();
			}
		}
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
	/*
	public function forcedValidationForObviousOneStepMentalComputation(){
		// ad'hoc method created to avoid the complexity to compare 
		// results from manual coding dued to its inconsistency
		if((count($this->simpl_fors_obj)==1)&&($this->simpl_fors_obj[0]->resol_typ==Type_de_Resolution::operation_mentale)){
			if(preg_match_all(RegexPatterns::number,$this->str, $ar_temp, PREG_SET_ORDER)==1){
				$this->logger->info("this formula is an obvious case of mental computation");
				return True;
			}
		
		}
		return False;
		
	}
	*/
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
				$n_moins=strval(intval($doublon[1])-intval($doublon[0])); //$doublon[1] always bigger because of sort
				$n_plus=strval(intval($doublon[1])+intval($doublon[0]));
				$blocPlus=["formula"=>$doublon[1].' + '.$doublon[0],"str"=>$doublon[1].' + '.$doublon[0].' = '.$n_plus];
				$blocMoins=["formula"=>$doublon[1].' - '.$doublon[0],"str"=>$doublon[1].' - '.$doublon[0].' = '.$n_moins];
				$alreadyAdded=False;
				if(in_array($n_plus,array_keys($availableMentalNumbers))){
					foreach($availableMentalNumbers[$n_plus] as $bloc){
						if(serialize($bloc)==serialize($blocPlus)){
							$alreadyAdded=True;
							break;
						}
						
					}
				}
					if($alreadyAdded==False){
						$availableMentalNumbers[$n_plus][]=$blocPlus;			
					}				
				$alreadyAdded=False;
				if(in_array($n_moins,array_keys($availableMentalNumbers))){
					foreach($availableMentalNumbers[$n_moins] as $bloc){
						if(serialize($bloc)==serialize($blocMoins)){
							$alreadyAdded=True;
							break;
						}	
					}
				}
					if($alreadyAdded==False){
						$availableMentalNumbers[$n_moins][]=$blocMoins;			
					}	
			}
		}
		$this->logger->info("availableMentalNumbers :");
		$this->logger->info(array_keys($availableMentalNumbers));
		/*$this->logger->trace("availableMentalNumbers : détails");// to uncomment whenever there is a way to read log file with filter trace
		$this->logger->trace($availableMentalNumbers);*/
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
		$this->formulaCount=count($this->simpl_formulas);
	}

	// Analyses a simple arithmetic problem answer.
	// WORKS ONLY FOR ADDITIONS / SUBSTRACTIONS!
	// NO NEGATIVE NUMBERS ALLOWED!
	public function	sequentialAnalysis($nbs_problem,$verbose=True)
	{
		/*
		 * Update Number list that can be reached by mental computation
		* */


		foreach ($this->simpl_formulas as $s=>$simpl_form)
		{
			$formlulaIsInterpretable=True;
			$nUnkowns=$this->unknownCount($simpl_form);
			$this->logger->info("analyse of this formula : $simpl_form");
			$this->logger->info("number of unkwowns numbers in the formula (1 is the simplest case) ");
			$this->logger->info($nUnkowns);
			if($nUnkowns>1){				
				$this->logger->info("we try to detect mental calculations");
				$mentalCalculations=$this->detectMentalCalculations($simpl_form);//
				$mentalCalculations=$this->reduceMentalCalculations($mentalCalculations);
				$nRealUnknowns=$nUnkowns-count($mentalCalculations);
				$this->logger->info("After the mental computation investigation, we count the number of remaining unkwowns (1 is the simplest case) ");
				$this->logger->info($nRealUnknowns);
				switch ($nRealUnknowns)
				{
					case 1 :
						foreach ($mentalCalculations as $mentalCalculation){
							$this->addFormula($mentalCalculation);
						}
						break;
						
					case 0 :
						$this->logger->info("We try to drop a mental formula");
						$next_form = (isset($this->simpl_formulas[$s+1])) ? $this->simpl_formulas[$s+1] : "";
						$mentalCalculations=$this->dropLeastProbableMentalCalculations($mentalCalculations,$simpl_form,$next_form);
						foreach ($mentalCalculations as $mentalCalculation){
							$this->addFormula($mentalCalculation);
						}
					break;		
					
					default:
						$formlulaIsInterpretable=False;
						$this->anomalyManager->addAnomaly("a formula has been considered ininterpretable");
						$this->logger->info("interpretation process of the current formula has failed at this point");
						break;
					//too many or not enough mental calculations to understand this operations
				}
			}
			if($formlulaIsInterpretable==True)
			{
				$formula=new SimplFormul($simpl_form, $nbs_problem, $this->simpl_fors,$this->logger,$this->policy,$this->lastElementComputed,$this->lastElementAfterEqualSign);
				$i=$this->addFormula($formula);	
				$this->updateAvailableNumbers();
				$this->updateAvailableMentalNumbers();
			}
		}
	}
	
	public function reduceMentalCalculations($mentalCalculations){
		//TODO: should use policy
		//should compute score with that
		//should raise warning
		try{
			$flatListOfMentalComp=[];
			foreach($mentalCalculations as $listOfMentalCalculation){
				$flatListOfMentalComp[]=$listOfMentalCalculation[0];
			}
		}catch (Exception $e) {
			echo 'Exception reçue : ',  $e->getMessage(), "\n";
		}
		return $flatListOfMentalComp;	
	}
	
	public function	globalAnalysis()
	{
		// todo detect RMI here
		if($this->finalAnswer!=""){
			$found=False;
			foreach (array_reverse($this->simpl_fors_obj) as $index=>$formOb){
				if ($formOb->result==$this->finalAnswer){
					$this->logger->info("final formula is $formOb->str ");
					$this->logger->info("then the summary formula is : $formOb->formul ");
					$this->finalFormula=$formOb->formul;
					$this->reverseIndexLastFormula=$index;
					return;
				}
			}
			if(in_array($this->finalAnswer,array_keys($this->availableMentalNumbers))){
					$lastMentalComputation=$this->availableMentalNumbers[$this->finalAnswer][0];//temp
					$formstr=$lastMentalComputation["str"];
					$this->logger->info("possible mental formula found : $formstr");
					$lastMentalCalculations=new MentalFormul($lastMentalComputation["str"], $this->nbs,$this->simpl_fors,$this->logger,$this->policy,$this->lastElementComputed,$this->lastElementAfterEqualSign);
					$this->logger->info("mental calculation suggested : ");
					$this->logger->info($lastMentalComputation["str"]);
					$this->simpl_fors_obj[]=$lastMentalCalculations;
					$this->simpl_fors[$lastMentalCalculations->result] = $lastMentalCalculations->formul;
				}
			else{
				$this->logger->info("NO FORMULA EXPLAINS THE NUMBER GIVEN AS AN ANSWER");
				$this->ininterpretable=True;
				return;
			}

		}
		//if no answer explitely given ones take the last formula as referent
		if(count($this->simpl_fors_obj)==0){ // if there is no "last formula => end
			
			$this->ininterpretable=True;//TODO : global status of the answer as an enum, it would allow more  precise information such as "no formula detected"
			$this->logger->info("No formula Found");
			return ;
		}
		else {
			$lform=end($this->simpl_fors_obj);
			$this->logger->info("final formula (by default) is $lform->str ");
			$this->logger->info("then the summary formula is : $lform->formul ");
			$this->finalFormula=$lform->formul;
			$this->reverseIndexLastFormula=1;
			}
		
		//TODO : 
		
	}
	
	public function dropLeastProbableMentalCalculations($listOfMentalCalculations,$simpl_form,$next_form){
		if($next_form=="") 
		// if the formula studied is the last formula, check if one number is given as an answer
			{
			foreach($listOfMentalCalculations as $i=>$mcal){
				if($mcal->result==$this->finalAnswer){
					$this->logger->info("We drop this mental computation because it's the final answer given by the student");
					$this->logger->info($listOfMentalCalculations[$i]->str);
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
						$this->logger->info($listOfMentalCalculations[$i]->str);
						$this->anomalyManager->addAnomaly("droped mental computation to disamb because giving an answer used later");
						unset($listOfMentalCalculations[$i]);
						return $listOfMentalCalculations;
					}
				}
			}
		}	
		// last case : consider that the number after the equal is the one genuinely computed
		// This is kind of risky, but it's a rare case.
		preg_match_all(RegexPatterns::lastNumberInFormula, $simpl_form, $n);
		$temp=end($n);
		$lastNumber=end($temp);
		foreach($listOfMentalCalculations as $j=>$mcal){
			if($mcal->result==$lastNumber){
				$this->logger->info("We drop the mental computation for the number after the equal (no better option)");
				$this->logger->info($listOfMentalCalculations[$j]->str);
				unset($listOfMentalCalculations[$j]);
				return $listOfMentalCalculations;
			}			
		}
		$this->logger->error("no mental computation has been droped, this is unexpected");
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
	
	public function addFormula($formula){
		$this->simpl_fors_obj[]=$formula;
		$this->simpl_fors[$formula->result] = $formula->formul;	
		$this->lastElementComputed=$formula->result;
		$this->currentLastFormObj=$formula;
		if($formula->resol_typ!=Type_de_Resolution::operation_mentale){
			$this->lastElementAfterEqualSign=$formula->nbs[2];
		}
		else{
			$this->lastElementAfterEqualSign="";
		}
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
	
	public function getAnomalies(){
		foreach($this->simpl_fors_obj as $form){
			foreach($form->possibleAnomalies as $anom){
				$this->anomalyManager->addAnomaly($anom);
			}
		}
		preg_match_all(RegexPatterns::number, $this->str, $nbs); //not the best place to add these anomalies
		$numbers=$nbs[0];
		foreach($numbers as $nb){
			if($nb!=$this->finalAnswer){
				 if(array_search($nb, $this->availableNumbers)===False){
				 	$this->anomalyManager->addAnomaly("unidentifiedNumber");
				 }
			}	
		}
	}
	
	public function detectMentalCalculations($formula,$context="disambFormula"){
		preg_match_all(RegexPatterns::number, $formula, $nbs);
		$numbersInFormula=$nbs[0];
		$mentalCalculations=[];
		foreach($numbersInFormula as $n){
			if(!in_array($n,$this->availableNumbers)||($context=="disambFinalAnswer")){//When we look at the final answer, 
				if(in_array($n,array_keys($this->availableMentalNumbers))){
					$mentalCalculations[$n]=[];
					foreach($this->availableMentalNumbers[$n] as $possibleMentalCalculation){
						$formstr=$possibleMentalCalculation["str"];
						$this->logger->info("possible mental formula found : $formstr");
						$nm=new MentalFormul($possibleMentalCalculation["str"], $this->nbs,$this->simpl_fors,$this->logger,$this->policy,$this->lastElementComputed,$this->lastElementAfterEqualSign,$this->currentLastFormObj);
						$u=array_diff($numbersInFormula, $nm->nbs);
							$mentalCalculations[$n][]=$nm; 
							$this->logger->info("mental calculation suggested : ");
							$this->logger->info($possibleMentalCalculation["str"]);
					}
				}
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
		self::$tabReplacements['french']['ensemble']=array('les deux','les 2');
	}
	
	public function repairSpecialFormulas(){
		
		preg_match_all(RegexPatterns::separatedFormula,$this->str, $matches,PREG_SET_ORDER);
		foreach($matches as $match){
			$uncompleteFormula=$match[1];
			$separatedNumber=$match[2];
			$result=strval(abs($this->eval->evaluate($uncompleteFormula)));
			if($result==$separatedNumber){
				$replacement=$uncompleteFormula.'='.$result;//a+b+c=d => a+b=y y+c=d
				$this->logger->info("uncomplete formula answer  found :");
				$this->logger->info($uncompleteFormula);
				$this->str=str_replace($uncompleteFormula,$replacement,$this->str);
			}
		}
		
		while (preg_match(RegexPatterns::compositeOperation,$this->str, $match)==1){
			$compositeFormula=$match[1];
			$result=strval($this->eval->evaluate($compositeFormula));
			$replacement=$compositeFormula.'='.$result.' '.$result;//a+b+c=d => a+b=y y+c=d
			$this->logger->info("composite formula answer  found :");
			$this->logger->info($compositeFormula);
			$this->str=str_replace($compositeFormula,$replacement,$this->str);
		}
			
		while (preg_match(RegexPatterns::followedUpOperation,$this->str, $match)==1){
			$compositeFormula=$match[1];
			$result=strval($this->eval->evaluate($compositeFormula));
			$replacement=$match[1].' '.$match[2];//a+b=c+d => a+b=y y+c=d, match[1] is a+b=y; match[2] is y
			$this->logger->info("followedUp formula answer  found :");
			$this->logger->info($compositeFormula);
			$this->str=str_replace($compositeFormula,$replacement,$this->str);
		}
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
