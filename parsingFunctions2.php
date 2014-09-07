<?php
function	get_tree($str)
{
	preg_match_all("[\d+]", $str, $nbs, PREG_SET_ORDER);
	var_dump($nbs);
}
?>
