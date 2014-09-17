<?php

class	Answer
{
	private	$str;

	public function	Answer($str)
	{
		$this->str = $str;
	}

	public function	print()
	{

	}

// Analyses a simple arithmetic problem answer.
// WORKS ONLY FOR ADDITIONS / SUBSTRACTIONS!
// NO NEGATIVE NUMBERS ALLOWED!
	public function	analyse($nbs_ennonce)
	{
		$formules_simples = find_simpl_for($reponse);
		$id_answer = insert_answer($reponse);
		foreach ($formules_simples as $formule_simple)
		{
			// Operation type
			$type_d_operation = find_op_typ($formule_simple[0]);
			// Resolution type
			preg_match_all("/\d+/", $formule_simple[0], $nbs_reponse);
			$type_de_resolution = find_resol_typ($nbs_ennonce, $nbs_reponse[0], $type_d_operation);
			// Calculation error
			$calcul_error = find_miscalc($nbs_reponse[0], $type_d_operation, $type_de_resolution);
			// SQL insertion
			insert_formula($id_answer, $formule_simple[0], $type_d_operation, $type_de_resolution, $calcul_error);
		}

	// Outputs:
	// - formules simples
	function	find_simpl_for($reponse)
	{
		preg_match_all("/\d+\s*[+*-\/]\s*\d+\s*=\s*\d+/",
			$reponse, $formules_simples, PREG_SET_ORDER);
		return $formules_simples;
	}
	// Outputs:
	// - resolution type as in enum Type_d_Resolution
	// Trick :
	// $nbs_ennonce a la forme " x, y, z,"
	// pour faciliter la reconnaissance des nombres
	// et ne pas confondre 4 et 45 par exemple.
	function	find_resol_typ(&$nbs_ennonce, $nbs_reponse, $type_d_operation)
	{
		$is_nb0 = strstr($nbs_ennonce, " ".$nbs_reponse[0].",");
		$is_nb1 = strstr($nbs_ennonce, " ".$nbs_reponse[1].",");
		// Test de la substraction inverse
		if ($type_d_operation === Type_d_Operation::substraction
			&& $nbs_reponse[0] < $nbs_reponse[1])
		{
			if ($is_nb0 === FALSE)
				$nbs_ennonce .= " ".$nbs_reponse[0].",";
			if ($is_nb1 === FALSE)
				$nbs_ennonce .= " ".$nbs_reponse[1].",";
			return Type_de_Resolution::substraction_inverse;
		}
		// Reste
		if ($is_nb0 !== FALSE)
		{
			if ($is_nb1 !== FALSE)
			{
				// On ajoute le resultat aux nombres connus :
				$nbs_ennonce .= " ".$nbs_reponse[2].",";
				return Type_de_Resolution::simple_operation;
			}
			else
			{
				$nbs_ennonce .= " ".$nbs_reponse[1].",";
				return Type_de_Resolution::operation_a_trou;
			}
		}
		else
		{
			if ($is_nb1 !== FALSE)
			{
				$nbs_ennonce .= " ".$nbs_reponse[0].",";
				return Type_de_Resolution::operation_a_trou;
			}
			else
			{
				$nbs_ennonce .= " ".$nbs_reponse[0].",";
				$nbs_ennonce .= " ".$nbs_reponse[1].",";
				return Type_de_Resolution::uninterpretable;
			}
		}
	}

	}

?>
