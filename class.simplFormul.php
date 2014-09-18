<?php

require_once('enums/enum.type_d_operation.php');
require_once('enums/enum.type_de_resolution.php');

class	SimplFormul
{
	private	$str;
	private	$nbs;
	private	$op_typ;
	private	$resol_typ;
	private	$miscalc;

	public function		SimplFormul($str, $nbs_problem)
	{
		$this->str = $str;
		preg_match_all("/\d+/", $str, $nbs);
		$this->find_op_typ();
		$this->find_resol_typ($nbs_problem);
		$this->find_miscalc();
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
		echo "<br />";
	}

	// Computes;
	// - Operation type as in enum Type_d_Operation
	private function	find_op_typ()
	{
		if (strstr($this->str, "+") !== FALSE)
			$this->op_typ = Type_d_Operation::addition;
		else if (strstr($this->str, "-") !== FALSE)
			$this->op_typ = Type_d_Operation::substraction;
	}

	// Outputs:
	// - resolution type as in enum Type_d_Resolution
	// Trick :
	// $nbs_problem a la forme " x, y, z,"
	// pour faciliter la reconnaissance des nombres
	// et ne pas confondre 4 et 45 par exemple.
	private function	find_resol_typ(&$nbs_problem)
	{
		$is_nb0 = strstr($nbs_problem, " ".$this->nbs[0].",");
		$is_nb1 = strstr($nbs_problem, " ".$this->nbs[1].",");
		// Test de la substraction inverse
		if ($this->op_typ === Type_d_Operation::substraction && $this->nbs[0] < $this->nbs[1])
		{
			if ($is_nb0 === FALSE)
				$nbs_problem .= " ".$this->nbs[0].",";
			if ($is_nb1 === FALSE)
				$nbs_problem .= " ".$this->nbs[1].",";
			return Type_de_Resolution::substraction_inverse;
		}
		// Reste
		if ($is_nb0 !== FALSE)
		{
			if ($is_nb1 !== FALSE)
			{
				// On ajoute le resultat aux nombres connus :
				$nbs_problem .= " ".$this->nbs[2].",";
				return Type_de_Resolution::simple_operation;
			}
			else
			{
				$nbs_problem .= " ".$this->nbs[1].",";
				return Type_de_Resolution::operation_a_trou;
			}
		}
		else
		{
			if ($is_nb1 !== FALSE)
			{
				$nbs_problem .= " ".$this->nbs[0].",";
				return Type_de_Resolution::operation_a_trou;
			}
			else
			{
				$nbs_problem .= " ".$this->nbs[0].",";
				$nbs_problem .= " ".$this->nbs[1].",";
				return Type_de_Resolution::uninterpretable;
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
				if (($result = (int)$this->nbs[0] + (int)$this->nbs[1]) === (int)$this->nbs[2])
					$this->miscalc = 0;
				else
					$this->miscalc = abs((int)$this->nbs[2] - $result);
				break;
			case Type_d_Operation::substraction :
				if ($this->resol_typ === Type_de_Resolution::substraction_inverse)
				{
					if (($result = (int)$this->nbs[1] - (int)$this->nbs[0]) === (int)$this->nbs[2])
						$this->miscalc = 0;
					else
						$this->miscalc = abs((int)$this->nbs[2] - $result);
				}
				if (($result = (int)$this->nbs[0] - (int)$this->nbs[1]) === (int)$this->nbs[2])
					$this->miscalc = 0;
				else
					$this->miscalc = abs((int)$this->nbs[2] - $result);
		}
	}


}

?>
