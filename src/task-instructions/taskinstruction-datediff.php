<?php

function nxs_task_instance_do_datediff($then_that_item, $taskid, $taskinstanceid)
{
	// $marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	// $state = $instancemeta["state"];
	
  $result = array();
  
  $first = $then_that_item["first"];
  $first = nxs_filter_translatesingle($first, "{{", "}}", $inputparameters);
  
	if ($first == "now")
	{
		$first_time = time();
	}
	else
	{
		$first_pieces = explode("T", $first);
		$first = $first_pieces[0];
		$first_time = strtotime($first);
	}
	
	$second = $then_that_item["second"];
	$second = nxs_filter_translatesingle($second, "{{", "}}", $inputparameters);
	if ($second == "now")
	{
		$second_time = time();
	}
	else
	{
		$second_pieces = explode("T", $second);
		$second = $second_pieces[0];
		$second_time = strtotime($second);
	}
	
	$diff = abs($first_time - $second_time);
	$human = nxs_time_getsecondstohumanreadable($diff);
	
	$result["console"][]= "datediff is $diff ($first $second); $human";
  
  //
  //
  //
  
  $result["result"] = "OK";
  
  return $result;
}