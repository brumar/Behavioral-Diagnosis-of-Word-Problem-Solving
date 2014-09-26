<?php

require_once('class.simplFormul.php');

class	Answer
{
	private	$str;
	private $simpl_formulas;
	private $simpl_fors;

	public function	Answer($str, $nbs_problem)
	{
		$this->str = $str;
		$this->analyse($nbs_problem);
		$this->simpl_fors = [];
	}

	public function	_print()
	{
		echo "voila";
	}

	// Outputs:
	// - formules simples
	function	find_simpl_for()
	{
		preg_match_all("/\d+\s*[+*-\/]\s*\d+\s*=\s*\d+/",
			$this->str, $this->simpl_formulas, PREG_SET_ORDER);
	}

	// Analyses a simple arithmetic problem answer.
	// WORKS ONLY FOR ADDITIONS / SUBSTRACTIONS!
	// NO NEGATIVE NUMBERS ALLOWED!
	public function	analyse($nbs_problem)
	{
		$this->find_simpl_for();
		//$id_answer = insert_answer($reponse);
		foreach ($this->simpl_formulas as $simpl_formula)
		{
			$tmp = new SimplFormul($simpl_formula[0], $nbs_problem);
			//$tmp->find_formul($nbs_problem, $this->simpl_fors);
			$tmp->_print();
			$this->simpl_fors[$tmp->result] = $tmp->formul;
			// SQL insertion
			// insert_formula($id_answer, $simpl_formula[0], $type_d_operation, $type_de_resolution, $calcul_error);
		}
		print_r($this->simpl_fors);
	}
}

?>
