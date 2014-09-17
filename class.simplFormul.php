<?php

class	SimplFormul
{
	private	$str;
	private	$nbs;
	private	$op_typ;
	private	$resol_typ;
	private	$miscalc;

	public function		SimplFormul($str)
	{
		$this->str = $str;
		preg_match_all("/\d+/", $str, $nbs);
		find_op_typ();
		find_miscalc();
	}

	public function		print()
	{
		echo "Formule : $str\n";
		echo "Type d'operation : $op_typ\n";
		echo "Type de resolution : $resol_typ\n";
		if ($miscalc > 0)
			echo "Contient une erreur de calcul de $miscalc.\n";
	}

	// Computes;
	// - Operation type as in enum Type_d_Operation
	private function	find_op_typ()
	{
		if (strstr($str, "+") !== FALSE)
			$op_typ = Type_d_Operation::addition;
		else if (strstr($str, "-") !== FALSE)
			$op_typ = Type_d_Operation::substraction;
	}
	
	// Outputs:
	// - calcul_error (int)
	function	find_miscalc()
	{ 
		switch($op_typ)
		{
			case Type_d_Operation::addition :
				if (($result = (int)$nbs_reponse[0] + (int)$nbs_reponse[1])
					=== (int)$nbs_reponse[2])
					return FALSE;
				else
					return abs((int)$nbs_reponse[2] - $result);
				break;
			case Type_d_Operation::substraction :
				if ($type_de_resolution === Type_de_Resolution::substraction_inverse)
				{
					if (($result = (int)$nbs_reponse[1] - (int)$nbs_reponse[0])
						=== (int)$nbs_reponse[2])
						return FALSE;
					else
						return abs((int)$nbs_reponse[2] - $result);
				}
				if (($result = (int)$nbs_reponse[0] - (int)$nbs_reponse[1])
					=== (int)$nbs_reponse[2])
					return FALSE;
				else
					return abs((int)$nbs_reponse[2] - $result);
		}
	}


}

?>
