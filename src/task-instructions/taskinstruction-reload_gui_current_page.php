<?php

function nxs_task_instance_do_reload_gui_current_page($then_that_item, $taskid, $taskinstanceid)
{
	$marker = $then_that_item["marker"];
	
	// $instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	// $inputparameters = $instancemeta["inputparameters"];
	
	// $state = $instancemeta["state"];
	
  $result = array();

	$random_reload_gui_token = time();
  $currenturl = "https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid={$taskid}&taskinstanceid={$taskinstanceid}&random_reload_gui_token={$random_reload_gui_token}#{$marker}";
  $result["console"][] = "<a href='{$currenturl}'>Reload this page</a>";
  
  //
  //
  //
  
  $result["result"] = "OK";
  
  return $result;
}