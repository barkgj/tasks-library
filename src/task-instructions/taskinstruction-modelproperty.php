<?php

function nxs_task_instance_do_modelproperty($then_that_item, $taskid, $taskinstanceid)
{
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$modeluri = $then_that_item["modeluri"];
	$modeluri = nxs_filter_translatesingle($modeluri, "{{", "}}", $inputparameters);
	
	$property = $then_that_item["property"];
	$property = nxs_filter_translatesingle($property, "{{", "}}", $inputparameters);	
	
	$shortcode = "[nxs_string ops=modelproperty modeluri='{$modeluri}' property='{$property}']";
	$value = do_shortcode($shortcode);
	
	//$result["console"][] = "shortcode: $shortcode";
	$result["console"][] = "$value";
	
  $result["result"] = "OK";
  
  return $result;
}