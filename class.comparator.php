<?php
require_once('class.evalmath.php');

class	Comparator
{
	public	$symbols;
	public	$evaluator;
	public	$s1;
	public	$s2;
	public $logger;

	public function		Comparator($symbols,$s1,$s2,$logger) //TODO: Would better without s1 and s2 as argument for constructor
	{
		$this->logger=$logger;
		$this->symbols=$symbols;
		$this->evaluator=new EvalMath;
		$t1=explode(" ", $s1);
		$this->s1=end($t1); // we only take the second part if there are two parts e.g T1-P1 T1+P1, only T1+P1 is taken
		$t2=explode(" ", $s2);
		$this->s2=end($t2);
		$this->replaceAsStrMin();
		$this->getRidOfspaces();
	}
	
	public function getRidOfspaces(){
		$this->s1=preg_replace('/\s+/', '',$this->s1);
		$this->s2=preg_replace('/\s+/', '',$this->s2);
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
		try {
		$this->evaluator->evaluate('f(t1,p1,d)='.$this->s1);
		$this->evaluator->evaluate('g(t1,p1,d)='.$this->s2);
		$res1 = $this->evaluator->evaluate('f(16,4,1)');
		$res2 = $this->evaluator->evaluate('g(16,4,1)');
		} catch (Exception $e) {
			echo('mathEval failed to compare '.$this->s1.' with '.$this->s2);
			return False;
		}
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