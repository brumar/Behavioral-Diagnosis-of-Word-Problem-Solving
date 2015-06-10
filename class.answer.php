<?php

require_once('class.simplFormul.php');
require_once('enums/enum.regexPatterns.php');

class	Answer
{
	private	$str;
	private	$nbs;//associative array "23"=>"N1"
	private $numbersInProblem;//"23", etc...
	
	private	$full_exp;
	private $simpl_formulas;//formulas as string
	private $simpl_fors; // bind computed numbers to their formula
	private	$simpl_fors_obj; //formulas as object


	public function	Answer($str, $nbs_problem)
	{
		$this->str = $str;
		$this->nbs=$nbs_problem;
		$this->numbersInProblem=array_keys($this->nbs);
		$this->simpl_fors = [];
		$this->analyse($nbs_problem);
	}

	public function	_print()
	{
		echo "voila";
	}

	// Outputs:
	// - formules simples
	function	find_simpl_for()
	{
		preg_match_all(RegexPatterns::completeOperation,
			$this->str, $this->simpl_formulas, PREG_SET_ORDER);
	}

	// Analyses a simple arithmetic problem answer.
	// WORKS ONLY FOR ADDITIONS / SUBSTRACTIONS!
	// NO NEGATIVE NUMBERS ALLOWED!
	public function	analyse($nbs_problem,$verbose=True)
	{
		$this->find_simpl_for();
		$this->sortFormulas();
		//$id_answer = insert_answer($reponse);
		$i = 0;
		foreach ($this->simpl_formulas as $simpl_formula)
		{
			$formula=new SimplFormul($simpl_formula[0], $nbs_problem, $this->simpl_fors);
			$this->simpl_fors_obj[$i]=$formula;
			if($verbose){
				$this->simpl_fors_obj[$i]->_print();
			}
			$this->simpl_fors[$this->simpl_fors_obj[$i]->result] = $this->simpl_fors_obj[$i]->formul;
			$i++;
		}
		$this->full_exp = $this->simpl_fors_obj[$i - 1]->formul;
//		print_r($this->simpl_fors);
	}
	
	public function sortFormulas()
	{
		$count=array();
		foreach ($this->simpl_formulas as $simpl_formula)
		{
			$unknownCount=$this->unknownCount($simpl_formula);
			$count[]=$unknownCount;
		}
		array_multisort($count, SORT_ASC,$this->simpl_formulas);
	}
	
	
	public function unknownCount($simpl_formula)
	{
		preg_match_all(RegexPatterns::number, $simpl_formula[0], $nbs);
		$numbersInFormula=$nbs[0];
		$c=count(array_diff($numbersInFormula,$this->numbersInProblem));
		return $c;
	}
}

?>
