<?php
require_once('class.evalmath.php');

class	Comparator
{
	public	$symbols;
	public	$evaluator;
	public	$s1;
	public	$s2;

	public function		Comparator($symbols,$s1,$s2) //TODO: Would better without s1 and s2 as argument for constructor
	{
		$this->symbols=$symbols;
		$this->evaluator=new EvalMath;
		$t1=explode(" ", $s1);
		$this->s1=end($t1); // we only take the second part if there are two parts e.g T1-P1 T1+P1, only T1+P1 is taken
		$t2=explode(" ", $s2);
		$this->s2=end($t2);
		$this->replaceAsStrMin();
	}
	
	public function compareExpressions(){
		if($this->s1==$this->s2){
			return True;
		}
		if(empty($this->s1)||empty($this->s2)){ // if both are void, the condition $s1==$s2 is valid
			return False;
		}
		return ($this->haveSameNumberOfSymbols() && $this->compareExpressionValue());
	}
	
	public function compareExpressionValue(){
		$this->evaluator->evaluate('f(t1,p1,d)='.$this->s1);
		$this->evaluator->evaluate('g(t1,p1,d)='.$this->s2);
		$res1 = $this->evaluator->evaluate('f(16,4,1)');
		$res2 = $this->evaluator->evaluate('g(16,4,1)');
		return ($res1==$res2);
	}
	
	public function haveSameNumberOfSymbols(){ 
		foreach($this->symbols as $symbol){
			$s1=substr_count($this->s1,$symbol);
			$s2=substr_count($this->s1,$symbol);
			if($s1!=$s2){
				return False;
			}
		}
		return True;
	
	}
	public function replaceAsStrMin(){
		foreach($this->symbols as $symbol){
			$this->s1=str_replace($symbol,strtolower($symbol),$this->s1);
			$this->s2=str_replace($symbol,strtolower($symbol),$this->s2);
		}
	}
}
?>