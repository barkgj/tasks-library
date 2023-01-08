<?php

function nxs_task_instance_do_conditional_wrapper_end($then_that_item, $taskid, $taskinstanceid)
{
	$marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];

  $result = array();
  
  $html = "";
  $html .= "</div></div>";

  $result["console"][] = $html;
  
  $result["result"] = "OK";
  
  return $result;
}