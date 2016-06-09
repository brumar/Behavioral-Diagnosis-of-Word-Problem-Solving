# Introduction  
This repository presents a PHP library destined to realize behavioral diagnosis on open answer in the field of additive word problem solving. There is a fair amount of scientific litterature on arithmetic word problem solving. If you are new to this field and want to dig it, I strongly recommend the book of Stephen K.Reed (Word Problems: Research and Curriculum Reform) which is a good read whatever the cognitive psychology background you have. It also presents few learning environnement related to word problem solving. Behavioral diagnosis is, in the context of a learning environement, the process by which actions of the student are untangled and formalised in the system in a way that it can be used for further investivations (like epistemic diagnosis). Student modeling is also a huge research domain. To know more about what is meant by diagnosis, its various forms and importance in the context of learning environnement. I recommend the review of Ragnelmam *"Student diagnosis in practice; bridging a gap"*. 


# How to use it
You are not forced to dig into the code to use this program. To diagnose a student answer you only need the numbers which are given in the problem and the string representing the answer of the student.

```php
require_once('class.answer.php');
$a=new Answer("3+4=7 they have 7 apples",["3"=>"a", "4"=>"b"],True); // the last parameter is optional (means verbose=True)
$a->process();
```

Other optionnal parameters are possibles. You can have a look on `class.answer.php` to dig up more into the code. `test.php` can also be usefull if you want to see how it has been used in practice to analyse the performance of the program accross a dataset of multiple problem and answers.


# Statistical Analysis
The subfolder R_analysis contains a knitr notebook related to a statistical analysis based on regular STAR outputs. The goal was to evaluate performances of the programm by comparing with human judgements. Theses analysis are enhanced by the "anomaly counter" computed during the diagnosis process. This counter has been made to detect unreliable diagnosis. Its validity is evaluated in the statistical analysis. To go one step further in comparing human and automatic diagnosis, an experimentation has been made to "discuss" points where the human and the computer were in disagreement. This investigation has been done by asking various judge to give their preferences. Here again, the usefullness of the "anomaly counter" has been assessed. To visualize the HTML file produced by knitr, please use the tool htmlpreview.github.io. 
 
# Important note
I do not own the empirical data used to validate the diagnosis model. If you envision a non-private way of using this data, please contact me and I'll redirect you towards the owner of the dataset.

# Last thing
Opening a research project is not an easy task. Please contact me if you are interested but have some difficulties to understand what's going on in this project or for any other reason related to the repository. If some people are interested I could make more substancial efforts to get this repository more "cognitively speaking" open.
