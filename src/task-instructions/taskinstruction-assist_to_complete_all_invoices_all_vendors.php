<?php

function nxs_task_instance_do_assist_to_complete_all_invoices_all_vendors($then_that_item, $taskid, $taskinstanceid)
{
	// $marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$state = $instancemeta["state"];
	
  $result = array();
  
  if ($state == "STARTED")
  {
  	$year = $then_that_item["year"];
  	
  	$errors = array();
  	
  	if ($year == "{{year}}" || $year == "")
  	{
  		$errors[] = "year not specified";
  	}
  	
  	$canproceed = (count($errors) == 0);
  	
  	if ($canproceed)
  	{
	  	if ($_REQUEST["acaiav_action"] == "")
	  	{
	  		$url = "https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid={$taskid}&taskinstanceid={$taskinstanceid}";
	  		$result["console"][] = "<form method='POST' action='$url'><input type=hidden name='acaiav_action' value='go' /><input type=submit value='Do it' /></form>";
	  	}
	  	else
	  	{
	  		// do it!, bulk create instances
	  		
	  		//$result["console"][] = "todo: loop over vendors, bulk create instance of 498 with given vendorid";
	  		
	  		$schema = "nxs.itil.configurationitems.vendor";
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
					$vendor_id = $entry["nxs.itil.configurationitems.vendor_id"];
					
					$args["items"][] = array
					(
						"taskid" => 498,
						"year" => $year,
						"vendor_id" => $vendor_id,
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
					nxs_webmethod_return_nack("error bulk creating instances for 498");
				}
				
				$result["console"][] = "finished creating instances of 498 for each vendor";
	  	}
	  }
	  else
	  {
	  	$result["console"][] = "unable to assist_to_complete_all_invoices_all_vendors:";	
	  	foreach ($errors as $error)
	  	{
	  		$result["console"][] = $error;
	  	}
	  }
  }
  else
  {
  	$result["console"][] = "nothing to do because of state";
  }
  
  //
  //
  //
  
  $result["result"] = "OK";
  
  return $result;
}