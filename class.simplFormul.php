<?php

require_once('enums/enum.type_d_operation.php');
require_once('enums/enum.type_de_resolution.php');

class	SimplFormul
{
	public	$str;
	public	$nbs;
	public	$op_typ;
	public	$resol_typ;
	public	$miscalc;

	public $result;
	public $formul;

	public function		SimplFormul($str, $nbs_problem, $simpl_fors)
	{
		$this->str = $str;
		$this->findNumbers();
		$this->find_op_typ();
		$this->find_resol_typ($nbs_problem, $simpl_fors);
		$this->find_miscalc();
	}
	
	public function findNumbers(){
		preg_match_all(RegexPatterns::number, $this->str, $nbs);
		$this->nbs = $nbs[0];				
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
	}

	// Outputs:
	// - resolution type as in enum Type_d_Resolution
	// Trick :
	// $nbs_problem a la forme " x, y, z,"
	// pour faciliter la reconnaissance des nombres
	// et ne pas confondre 4 et 45 par exemple.
	private function	find_resol_typ($nbs_problem, $simpl_fors)
	{
		$is_nb0 = array_key_exists($this->nbs[0], $nbs_problem);
		if ($is_nb0 === FALSE)
			$is_nb0 = array_key_exists($this->nbs[0], $simpl_fors);
		$is_nb1 = array_key_exists($this->nbs[1], $nbs_problem);
		if ($is_nb1 === FALSE)
			$is_nb1 = array_key_exists($this->nbs[1], $simpl_fors);
		// Test de la substraction inverse
		if ($this->op_typ === Type_d_Operation::substraction && $this->nbs[0] < $this->nbs[1])
		{
			$this->resol_typ = Type_de_Resolution::substraction_inverse;
			$this->result = $this->nbs[2];
			$this->formul = $nbs_problem[$this->nbs[1]] . $this->formul;
			$this->formul .= $nbs_problem[$this->nbs[0]];
		}
		// Reste
		else if ($is_nb0 !== FALSE)
		{
			if ($is_nb1 !== FALSE)
			{
				$this->resol_typ = Type_de_Resolution::simple_operation;
				$this->result = $this->nbs[2];
				if (array_key_exists($this->nbs[0], $nbs_problem) !== FALSE)
					$this->formul = $nbs_problem[$this->nbs[0]] . $this->formul;
				else
					$this->formul = "(" . $simpl_fors[$this->nbs[0]] . ")" . $this->formul;
				if (array_key_exists($this->nbs[1], $nbs_problem) !== FALSE)
					$this->formul .= $nbs_problem[$this->nbs[1]];
				else
					$this->formul .= "(" . $simpl_fors[$this->nbs[1]] . ")";
			}
			else
			{
				$this->result = $this->nbs[1];
				// Test de la soustraction par l'addition a trou
				if ($this->op_typ === Type_d_Operation::addition)
				{
					$this->op_typ = Type_d_Operation::substraction;
					$this->resol_typ = Type_de_Resolution::addition_a_trou;
					$this->formul = $nbs_problem[$this->nbs[2]] . " - ";
					$this->formul .= $nbs_problem[$this->nbs[0]];
				}
				else	// soustraction a trou standard
				{
					$this->resol_typ = Type_de_Resolution::operation_a_trou;
					$this->formul = $nbs_problem[$this->nbs[0]] . $this->formul;
					$this->formul .= $nbs_problem[$this->nbs[2]];
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
					$this->formul = $nbs_problem[$this->nbs[2]] . " + ";
					$this->formul .= $nbs_problem[$this->nbs[1]];
				}
				// Test de la soustraction par l'addition a trou
				else if ($this->op_typ === Type_d_Operation::addition)
				{
					$this->op_typ = Type_d_Operation::substraction;
					$this->resol_typ = Type_de_Resolution::addition_a_trou;
					$this->formul = $nbs_problem[$this->nbs[2]] . " - ";
					$this->formul .= $nbs_problem[$this->nbs[1]];
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
