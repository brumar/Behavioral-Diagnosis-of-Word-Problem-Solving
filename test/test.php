<html>
<head>
<meta charset="utf-8">
</head>
<body>
<?php
require_once('../simple_analysis.php');
require_once('../class.answer.php');
require_once('../class.comparator.php');
require_once('../enums/enum.decision_policy.php');//name : DecPol
// The numbers for each problem
$numbers=array();
$numbers["Tp2_v2"]=array("5"=>"P1", "14"=>"T1", "2"=>"d");
$numbers["Tt1_v2"]=array("6"=>"P1", "15"=>"T1", "4"=>"d");
$numbers["Tt3_v2"]=array("6"=>"P1", "13"=>"T1", "2"=>"d");
$numbers["Cp1_v2"]=array("7"=>"P1", "16"=>"T1", "4"=>"d");
$numbers["Cp2_v2"]=array("9"=>"P1", "15"=>"T1", "4"=>"d");
$numbers["Ct2_v2"]=array("9"=>"P1", "15"=>"T1", "4"=>"d");
$numbers["Cp3_v2"]=array("9"=>"P1", "17"=>"T1", "3"=>"d");
$numbers["Ct1_v2"]=array("7"=>"P1", "16"=>"T1", "4"=>"d");
$numbers["Ct3_v2"]=array("9"=>"P1", "17"=>"T1", "3"=>"d");
$numbers["Cp4_v2"]=array("7"=>"P1", "15"=>"T1", "3"=>"d");
$numbers["Ct4_v2"]=array("7"=>"P1", "15"=>"T1", "3"=>"d");
$numbers["Tp1_v2"]=array("6"=>"P1", "15"=>"T1", "4"=>"d");
$numbers["Tt2_v2"]=array("5"=>"P1", "14"=>"T1", "2"=>"d");
$numbers["Tp3_v2"]=array("6"=>"P1", "13"=>"T1", "2"=>"d");
$numbers["Tp4_v2"]=array("7"=>"P1", "12"=>"T1", "3"=>"d");
$numbers["Tt4_v2"]=array("7"=>"P1", "12"=>"T1", "3"=>"d");

$numbers["Ct1_v1"]=array("5"=>"P1", "12"=>"T1", "3"=>"d");
$numbers["Cp1_v1"]=array("5"=>"P1", "12"=>"T1", "3"=>"d");
$numbers["Ct2_v1"]=array("6"=>"P1", "15"=>"T1", "2"=>"d");
$numbers["Cp2_v1"]=array("6"=>"P1", "15"=>"T1", "2"=>"d");
$numbers["Ct3_v1"]=array("6"=>"P1", "15"=>"T1", "2"=>"d");
$numbers["Cp3_v1"]=array("6"=>"P1", "15"=>"T1", "2"=>"d");
$numbers["Ct4_v1"]=array("9"=>"P1", "14"=>"T1", "2"=>"d");
$numbers["Cp4_v1"]=array("9"=>"P1", "14"=>"T1", "2"=>"d");
$numbers["Tt4_v1"]=array("5"=>"P1", "12"=>"T1", "3"=>"d");
$numbers["Tp4_v1"]=array("5"=>"P1", "12"=>"T1", "3"=>"d");
$numbers["Tt1_v1"]=array("7"=>"P1", "16"=>"T1", "3"=>"d");
$numbers["Tp3_v1"]=array("7"=>"P1", "16"=>"T1", "2"=>"d");
$numbers["Tt2_v1"]=array("5"=>"P1", "14"=>"T1", "2"=>"d");
$numbers["Tp2_v1"]=array("5"=>"P1", "14"=>"T1", "2"=>"d");
$numbers["Tt3_v1"]=array("7"=>"P1", "16"=>"T1", "2"=>"d");
$numbers["Tp1_v1"]=array("7"=>"P1", "16"=>"T1", "3"=>"d");

$row = 1;
set_time_limit(300);
$evaluate=True;
$compare=True;
$toBeSerialized=True;
$policy=[DecPol::lastComputed,DecPol::computed,DecPol::problem,DecPol::afterEqual];
$count2=0;
if($evaluate){
	if ((($handleInput = fopen("AllDataOnlyCompleteFormula.csv", "r")) !== FALSE)&&(($handleOutput = fopen("comparison2.csv", "w")) !== FALSE)) {
		$titles = fgetcsv($handleInput, 541, ";"); //pop the first line (headers of columns)
		$titles2=array("problem","answer");
		for ($i=2;$i<count ($titles);$i++)
		{
			$t=$titles[$i];
			$titles2[]=$t;
			$titles2[]="[parsed] $t";
		}
		fputcsv($handleOutput,$titles2,";");// we place the new headers for the output
	    while (($data = fgetcsv($handleInput, 541, ";")) !== FALSE) {
	    	$row++;
	    	// READ THE CSV 
	        $num = count($data);
	        $problem=$data[0];
	        $answer=$data[1];
	        echo(" index row : $row <br>");
	        //var_dump($a);
	        //$analyse=$a->full_exp;
	        //echo ("$problem => $answer....Analyse=>$analyse<br>");
	        
	        // DIAGNOSE
	
	        // NB (1) :sour THE ABSOLUTE PRIORITY IS completeformula in $globalAnalysis, 
	        // NB (2) : In case of doubts about how to fill the values,  if not solved by reading the CSV within 2 minutes, contact me :)
	        
	        $elements = array('formula', 'operation','correct_computation', 'correct_identification');      
	        $etape = array_fill_keys($elements, '');  //init the values with ""
	        $difference = array_fill_keys($elements, ''); //init the values with ""
	        
	        $elementsGlobalAnalysis= array('completeformula', 'operation','correct_computation', 'correct_identification');
	        $globalAnalysis = array_fill_keys($elementsGlobalAnalysis, '');
	        
	        
	        /*
	         * 
	         * CALL YOUR PARSING STUFF THERE
	         * The goal is to fill these arrays
	         * THE ABSOLUTE PRIORITY IS completeformula in $globalAnalysis*/
	        
	        $problemNumbers=$numbers[$problem];
	        print($row);

	        $a=new Answer($answer, $numbers[$problem],True,"french",strval($row),$policy);
	        $last_for_obj=end($a->simpl_fors_obj);
	        $globalAnalysis["completeformula"]=str_replace(' ', '',$last_for_obj->formul);
	        if($last_for_obj->resol_typ==Type_de_Resolution::operation_mentale){
	        	if(empty($data[10])){
	        		$data[10]="itsok";
	        	}
	        }
	         

	        
	        
	        
	        // ************ SOME INDICATIONS ABOUT THE VALUES*******************
	        
	        // possible values for operation (exhaustive) :
	        // addition
	        // soustraction à trou
	        // soustraction
	        // opération mentale
	        // addition à trou
	        // soustraction inversée
	        // soustraction à trou
	        // "" //by default if no computation
	        	
	        // possible values for correct_computation (exhaustive) :
	        //oui
	        //non
	        // "" //by default
	        
	        
	        // [not a priority] possible values for correct_computation (exhaustive) :
	        //rbi
	        //rmi
	        // ""
	        
	 
	        // ************ SOME INDICATIONS ABOUT ETAPE AND DIFFERENCE (WHEN TO ADD VALUES)*******************
	        
	        // IF CALCUL INTERMEDIAIRE as "T1-P1" or "T1-d" or "P1-d" in $computations
		        // if "T1-P1" is in $computations
		        // then update $etape
		        
		        // if "T1-d" or "P1-d" is in $computations
		        // then update $difference
		        // if both are present, update difference according T1-d
	        
		        
	        // ************ SOME INDICATIONS ABOUT GLOBAL ANALYSIS  *******************
	        
	        // conventions for completeformula :
	        	// IF THREE computations then display left to right : (firstcomputation) +/- (secondcomputation)
	        	// FOR EACH COMPUTATION the bigger number appears first : T1>P1>d example : T1+P1
	        	// NO blank space, except if 2 computations are unrelated. Example: T1-P1 T1-d
	        	
	        	// operation, correct_computation, correct_identification are related to the LAST computation made by the subject
	        	
	        $input=array($problem,$answer);
	        //array('formula', 'operation','correct_computation', 'correct_identification')
	        $etapeLine=array($data[2],$etape["formula"],$data[3],$etape["operation"],$data[4],$etape["correct_computation"],$data[5],$etape["correct_identification"]);
	        $differenceline=array($data[6],$difference["formula"],$data[7],$difference["operation"],$data[8],$difference["correct_computation"],$data[9],$difference["correct_identification"]);
	        $globalLine=array($data[10],$globalAnalysis["completeformula"],$data[11],$globalAnalysis["operation"],$data[12],$globalAnalysis["correct_computation"],$data[13],$globalAnalysis["correct_identification"],strval($a->anomalyManager->evalAnomalies()));
	        $lineForOutput=array_merge($input,$etapeLine,$differenceline,$globalLine);       
	        fputcsv($handleOutput,$lineForOutput,";");		
	    }
	    fclose($handleInput);
	    fclose($handleOutput);
	}
}
if($compare){

// ETABLISHING SUCESS RATE 
	$handleOutput_errors = fopen("50-100Errors_next.csv", "w");
	$handleOutput_anom = fopen("SuccessVsAnom.csv", "w");
	$target=18;//INDEX of the target
	$success=0;
	$count=0;
	if ((($handleInput = fopen("comparison2.csv", "r")) !== FALSE)) {
		$titles = fgetcsv($handleInput, 541, ";"); //pop the first line (headers of columns)
		while (($data = fgetcsv($handleInput, 541, ";")) !== FALSE) {
			$t_val=$data[$target];
			$t_adel=$data[$target+1];
			$comp= new Comparator(["T1","P1","d"], $t_val, $t_adel,Logger::getLogger("main"));
			$s=($t_val=="itsok")||($comp->compareExpressions()==True);
			if($s){
	 			$success++;
			}
			else if($count-$success<100){
				fputcsv($handleOutput_errors,$data,";");
			}
			$anom=[$s,$data[26]];
			fputcsv($handleOutput_anom,$anom,";");
			$count++;
		}
		$rapport=($success/$count)*100;
		echo "success rate over the $target collumn is $rapport";

	}
	fclose($handleInput);
	fclose($handleOutput_anom);
	fclose($handleOutput_errors);
}
?>
</body>
</html>