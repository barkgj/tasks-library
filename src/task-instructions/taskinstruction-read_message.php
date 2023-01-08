<?php

function nxs_task_instance_do_read_message($then_that_item, $taskid, $taskinstanceid)
{
	// $marker = $then_that_item["marker"];
	
	// $instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	// $inputparameters = $instancemeta["inputparameters"];
	
	// $state = $instancemeta["state"];
	
  $result = array();
  
  $result["console"][] = "<a target='_blank' href='https://global.nexusthemes.com/?nxs=task-gui&page=viewmessage&taskid={$taskid}&taskinstanceid={$taskinstanceid}'>Read message</a>";
  
  //
  //
  //
  
  $result["result"] = "OK";
  
  return $result;
}