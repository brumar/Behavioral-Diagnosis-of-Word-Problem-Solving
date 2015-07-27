<?php

require_once('enums/enum.type_d_operation.php');
require_once('enums/enum.type_de_resolution.php');
require_once('enums/enum.decision_policy.php');//name : DecPol
require_once('enums/enum.simulation_arguments.php');

class	SimplFormul
{
	public  $lastForm;
	public  $policy;
	public  $rmi;
	public	$str;//--Brut
	public	$nbs;
	public	$op_typ;//--Type
	public	$resol_typ;//--Forme
	public	$miscalc;//--Erreurs de calcul

	public $result;//--Résultat
	public $formul;//--Symbolique
	public $logger;
	
	public $simplFors;
	public $nbProblem;

	public $lastElementAfterEqualSign;
	public $lastElementComputed;
	public $numberReliabilityScore=[];
	public $possibleAnomalies=[];

	public function		SimplFormul($str, $nbs_problem, $simpl_fors,$logger,$pol,$lastElementComputed="",$lastElementAfterEqualSign="",$lastForm)
	{
		$this->lastForm=$lastForm;
		$this->rmi=False;
		$this->policy=$pol;
		$this->lastElementComputed=$lastElementComputed;
		$this->lastElementAfterEqualSign=$lastElementAfterEqualSign;
		$this->nbProblem=$nbs_problem;
		$this->simplFors=$simpl_fors; // TODO: Encapsulation principle
		$this->logger=$logger;
		$this->str = $str;
		$this->logger->info("more precise investigation of the formula $str");
		$this->findNumbers();
		$this->find_op_typ();
		$this->repairSign();
		$this->find_resol_typ($nbs_problem, $simpl_fors);
		$this->find_miscalc();
		$this->logSummary();
	}
	
	public function findNumbers(){
		preg_match_all(RegexPatterns::number, $this->str, $nbs);
		$this->nbs = $nbs[0];
		$this->logger->info("numbers found : ");
		$this->logger->info($this->nbs);
	}
	
	public function logSummary(){
		{
			$top=print_tdo($this->op_typ,True);
			$tres=print_tdr($this->resol_typ,True);
			$this->logger->info("Formule : $this->str");
			$this->logger->info("Type d'operation : $top ");
			$this->logger->info("Type de resolution : $tres ");
			if ($this->miscalc > 0)
				$this->logger->info("Contient une erreur de calcul de $this->miscalc.");
			$this->logger->info("Expression : $this->formul");
			}
	}

	public function		_print()
	{
		echo "Formule : $this->str<br />";
		echo "Type d'operation : ";
		print_tdo($this->op_typ);
		echo "<br />";
		echo "Type de resolution : ";
		print_tdr($this->resol_typ);
		echo "<br />";
		if ($this->miscalc > 0)
			echo "Contient une erreur de calcul de $this->miscalc.<br />";
		echo "Expression : $this->formul<br />";
		echo "<br />";
	}

	// Computes;
	// - Operation type as in enum Type_d_Operation
	private function	find_op_typ()
	{
		if (strstr($this->str, "+") !== FALSE)
		{
			$this->op_typ = Type_d_Operation::addition;
			$this->formul = " + ";
		}
		else if (strstr($this->str, "-") !== FALSE)
		{
			$this->op_typ = Type_d_Operation::substraction;
			$this->formul = " - ";
		}
		$this->logger->info("signe de l'opération : ");
		$this->logger->info($this->formul);
	}

	private function	repairSign() //in case of formulas like 6-4=10 or 6+4=2 
	{
		if (($this->formul == " + ")&&(abs($this->nbs[0]-$this->nbs[1])==$this->nbs[2]))
		{
			$this->formul = " - ";
			$this->op_typ = Type_d_Operation::substraction;
			$this->logger->info("we reverted the sign of the operation");
		}
			if (($this->formul == " - ")&&($this->nbs[0]+$this->nbs[1])==$this->nbs[2])
		{
			$this->formul = " + ";
			$this->op_typ = Type_d_Operation::addition;
			$this->logger->info("we reverted the sign of the operation");
		}
	}

	private function	historyOf($nb) //nb is under its numeric form and is turned into its symbolic form like T1, P1, P1-d etc....
	{
		//$policy=[DecPol::lastComputed,DecPol::afterEqual,DecPol::computed,DecPol::problem]
		$solutions=[];
		foreach($this->policy as $option){
			$solution=$this->callHistoryOf($nb,$option);
			if($solution!=""){
				$solutions[$option]=$solution;
			}
		}
		$this->checkForDoubts(array_keys($solutions));
		//now handle the case where multiple solutions are possible => raise warning
		if(Sargs::backtrackPolicy!=Sargs_value::random){
			$finalVal=array_values($solutions)[0];
		}
		else{
			$finalVal=array_values($solutions)[mt_rand(0, count($solutions) - 1)];
		}
		$optionSelected=array_search($finalVal,$solutions);
		$policyRank=count($this->policy)-array_search($optionSelected, $this->policy);
		$this->numberReliabilityScore[$nb]=$policyRank;
		return $finalVal;
		//$lastForm
	}
	
	private function checkForDoubts($solutions){
		if(in_array(DecPol::afterEqual, $solutions)&&(!in_array(DecPol::computed, $solutions))){
			$this->possibleAnomalies[]="warning : number after equal and not computed";
			//TODO: Turn this into enum whenever possible
		}
		if(in_array(DecPol::afterEqual, $solutions)&&(!in_array(DecPol::computed, $solutions))&&(!in_array(DecPol::problem, $solutions))){
			$this->possibleAnomalies[]="warning : number after equal and not in problem numbers";				
		}
		if(in_array(DecPol::computed, $solutions)&&(in_array(DecPol::problem, $solutions))){
			$this->possibleAnomalies[]="computed Number is also a problem number";			
		}
	}
	
	public function computeReliabilityScore(){
		$score=0;
		$score+=count($this->possibleAnomalies)*20;
		foreach($this->numberReliabilityScore as $numScore){
			$score+=$numScore;
		}
		return $score;
		
	}
	
	private function callHistoryOf($nb,$option){
		switch($option){
			case DecPol::afterEqual:
				if($nb==$this->lastElementAfterEqualSign){
					if(!in_array($nb,array_keys($this->simplFors))){
						return "(" . $this->lastForm->formul . ")";
					}
				}
				else{
					return "";
				}
				break;
			
			case DecPol::computed:
				if(in_array($nb,array_keys($this->simplFors))){
					return "(" . $this->simplFors[$nb] . ")";
				}
				else{
					return "";
				}
				break;
					
			case DecPol::lastComputed:
				if($nb==$this->lastElementComputed){
					return "(" . $this->simplFors[$nb] . ")";
				}
				else{
					return "";
				}
				break;
						
			case DecPol::problem:
				if (array_key_exists($nb, $this->nbProblem)){
					return $this->nbProblem[$nb];
				}
				else {
					return "";
				}
				break;
							
			default:
				return "";
		}
		/*if (array_key_exists($nb, $this->nbProblem))
			return $this->nbProblem[$nb];
		else
			return "(" . $this->simplFors[$nb] . ")";*/
	}
	// Outputs:
	// - resolution type as in enum Type_d_Resolution
	// Trick :
	// $nbs_problem a la forme " x, y, z,"
	// pour faciliter la reconnaissance des nombres
	// et ne pas confondre 4 et 45 par exemple.
	private function	find_resol_typ($nbs_problem, $simpl_fors)
	{
		$this->logger->info("try to find operation type");

		$is_nb0 = array_key_exists($this->nbs[0], $nbs_problem);
		if ($is_nb0 === FALSE)
			$is_nb0 = array_key_exists($this->nbs[0], $simpl_fors); 
			// if the number is not in the pbms, we look if it's in another formula
		$is_nb1 = array_key_exists($this->nbs[1], $nbs_problem);
		if ($is_nb1 === FALSE)
			$is_nb1 = array_key_exists($this->nbs[1], $simpl_fors);
		// Test de la substraction inverse

		// Reste
		if ($is_nb0 !== FALSE)
		{
			if ($is_nb1 !== FALSE)
			{
				if ($this->op_typ === Type_d_Operation::substraction && $this->nbs[0] < $this->nbs[1])
				{
					$this->resol_typ = Type_de_Resolution::substraction_inverse;
					$this->result = $this->nbs[2];
					$this->formul = $this->historyOf($this->nbs[1]) . $this->formul;
					$this->formul .= $this->historyOf($this->nbs[0]);
				}
				else{
					$this->resol_typ = Type_de_Resolution::simple_operation;
					$this->result = $this->nbs[2];
					$this->formul = $this->historyOf($this->nbs[0]) . $this->formul;
					$this->formul .= $this->historyOf($this->nbs[1]);
				}

			}
			else
			{
				$this->result = $this->nbs[1];
				// Test de la soustraction par l'addition a trou
				if ($this->op_typ === Type_d_Operation::addition)
				{
					$this->op_typ = Type_d_Operation::substraction;
					$this->resol_typ = Type_de_Resolution::addition_a_trou;
					$this->formul = $this->historyOf($this->nbs[2]) . ' - ';
					$this->formul .= $this->historyOf($this->nbs[0]);
				}
				else	// soustraction a trou standard
				{
				$this->resol_typ = Type_de_Resolution::operation_a_trou;
				$this->formul = $this->historyOf($this->nbs[0]) . $this->formul;
				$this->formul .= $this->historyOf($this->nbs[2]);
				}
			}
		}
		else
		{
			if ($is_nb1 !== FALSE)
			{
				$this->result = $this->nbs[0];
				// Test de l'addition par la soustraction a trou
				if ($this->op_typ === Type_d_Operation::substraction)
				{
					$this->op_typ = Type_d_Operation::addition;
					$this->resol_typ = Type_de_Resolution::substraction_a_trou;
					$this->formul = $this->historyOf($this->nbs[2]) . " + ";
					$this->formul .= $this->historyOf($this->nbs[1]);
				}
				// Test de la soustraction par l'addition a trou
				else if ($this->op_typ === Type_d_Operation::addition)
				{
					$this->op_typ = Type_d_Operation::substraction;
					$this->resol_typ = Type_de_Resolution::addition_a_trou;
					$this->formul = $this->historyOf($this->nbs[2]) . " - ";
					$this->formul .= $this->historyOf($this->nbs[1]);
				}
			}
			else
			{
				$this->resol_typ = Type_de_Resolution::uninterpretable;
				$this->result = $this->nbs[2];

			}
		}
	}

	// Outputs:
	// - calcul_error (int)
	private function	find_miscalc()
	{ 
		switch($this->op_typ)
		{
			case Type_d_Operation::addition :
				if ($this->resol_typ === Type_de_Resolution::substraction_a_trou)
					$this->miscalc = abs((int)$this->nbs[2] - (int)$this->nbs[0] + (int)$this->nbs[1]);
				else
					$this->miscalc = abs((int)$this->nbs[2] - (int)$this->nbs[0] - (int)$this->nbs[1]);
				break;
			case Type_d_Operation::substraction :
				if ($this->resol_typ === Type_de_Resolution::addition_a_trou)
					$this->miscalc = abs((int)$this->nbs[2] - (int)$this->nbs[0] - (int)$this->nbs[1]);
				else if ($this->resol_typ === Type_de_Resolution::substraction_inverse)
					$this->miscalc = abs((int)$this->nbs[2] - (int)$this->nbs[1] + (int)$this->nbs[0]);
				else
					$this->miscalc = abs((int)$this->nbs[2] - (int)$this->nbs[0] + (int)$this->nbs[1]);
		}
	}


}

?>
