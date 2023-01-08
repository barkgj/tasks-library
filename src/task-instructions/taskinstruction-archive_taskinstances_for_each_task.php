<?php

function nxs_task_instance_do_archive_taskinstances_for_each_task($then_that_item, $taskid, $taskinstanceid)
{
	if (!nxs_tasks_isheadless())
	{
		$result["console"][] = "Sorry, only implemented for headless environment";
	}
	else
	{
		// $marker = $then_that_item["marker"];
		
		$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
		// $inputparameters = $instancemeta["inputparameters"];
		
		$state = $instancemeta["state"];
		
	  $result = array();
	  
	  if (false)
	  {
	  	//
	  }
	  else if ($state == "STARTED")
	  {
			// make a distinct list of all tasks
			$schema = "nxs.p001.businessprocess.task";
			global $nxs_g_modelmanager;
			$a = array
			(
				"singularschema" => $schema,
			);
			$allentries = $nxs_g_modelmanager->gettaxonomypropertiesofallmodels($a);
			
			// bulk create task instances for each item
			$args = array();
			foreach ($allentries as $entry)
			{
				$archive_taskid = intval($entry["nxs.p001.businessprocess.task_id"]);
				$taskid_to_create = 241;
				
				$args["items"][] = array
				(
					"taskid" => $taskid_to_create,
					"archive_taskid" => $archive_taskid,
					"createdby_taskid" => $taskid,
					"createdby_taskinstanceid" => $taskinstanceid
				);
			}
			
			$args_json = json_encode($args);	
				
			$action_args = array
			(
				"url" => "https://global.nexusthemes.com/api/1/prod/create-bulk-task-instances/",
				"method" => "POST",
				"postargs" => array
				(
					"nxs" => "businessprocess-api",
					"nxs_json_output_format" => "prettyprint",
					"args_json" => $args_json
				)
			);		
			$action_string = nxs_geturlcontents($action_args);
			$action_result = json_decode($action_string, true);
			if ($action_result["result"] != "OK") 
			{
				nxs_webmethod_return_nack("nack; while posting for bulk creation of task instances");
			}
			
			// $result["bulk_create_result"] = $action_result;
	  }
	  else
	  {
	  	$result["console"][] = "Nothing to do because of state $state";
	  }
	}
  
  //
  //
  //
  
  $result["result"] = "OK";
  
  return $result;
}