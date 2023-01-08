<?php

function nxs_task_instance_do_assist_to_refine_qualification_manually($then_that_item, $taskid, $taskinstanceid)
{
	$result = array();

	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$refined_to_taskid = $inputparameters["refined_to_taskid"];
	$helpscoutthreadid = $inputparameters["helpscoutthreadid"];
	
	if ($refined_to_taskid == "")
	{
		$taskid = $_REQUEST["taskid"];
		global $nxs_g_modelmanager;
		$a = array("modeluri" => "{$taskid}@nxs.p001.businessprocess.task");
		$properties = $nxs_g_modelmanager->getmodeltaxonomyproperties($a);
		
		$qualification_refinement_taskids = $properties["qualification_refinement_taskids"];
		$possibilities = explode(";", $qualification_refinement_taskids);
		
		// extend the possibilities using the taskids as declared in the .workflows.json file
		// (if task is automated)
		$processingtype = nxs_tasks_getprocessingtype($taskid);
		if ($processingtype == "automated")
		{
			require_once("/srv/generic/libraries-available/nxs-workflows/nxs-workflows.php");
			$workflows = nxs_task_get_workflows($taskid);
			foreach ($workflows as $workflow)
			{
				$then_that_items = $workflow["then_that_items"];
				foreach ($then_that_items as $then_that_item)
				{
					$then_that_item_type = $then_that_item["type"];
					if ($then_that_item_type == "create_task_instance")
					{
						$create_taskid = $then_that_item["create_taskid"];
						$possibilities[] = $create_taskid;
						$automatedworkpossibilities[$create_taskid] = true;
					}
				}
			}
		}
		
		// get rid of empty items
		$possibilities = array_unique($possibilities);
		
		$result["console"][] = do_shortcode("[nxs_p001_task_instruction type='read_message']");
		
		$result["console"][] = "Check if any of the following possible qualifications apply:";
		foreach ($possibilities as $possible_taskid)
		{
			$refineurl = "";
			$currenturl = nxs_geturlcurrentpage();
			$returnurl = $currenturl;
			$title = nxs_tasks_gettaskstitle($possible_taskid);
			$from_where = "";
			if ($automatedworkpossibilities[$possible_taskid] == true)
			{
				$from_where .= " (from automated wf)";
			}
			$result["console"][] = "* Possibility : {$title} ({$possible_taskid}) {$from_where}";
			$button_text = "Qualify as $title";
			$form = <<<EOD
				** <form style='display: inline-block;' action='https://global.nexusthemes.com/' method='POST'>
					<input type='hidden' name='nxs' value='task-gui' />
					<input type='hidden' name='action' value='updateparameter' />
					<input type='hidden' name='page' value='taskinstancedetail' />
					<input type='hidden' name='taskid' value='{$taskid}' />
					<input type='hidden' name='taskinstanceid' value='{$taskinstanceid}' />
					<input type='hidden' name='name' value='refined_to_taskid' />
					<input type='hidden' name='value' value='{$possible_taskid}' />
					<input type='hidden' name='returnurl' value='{$returnurl}' />
					<input type='submit' value='{$button_text}' style='background-color: #CCC;'>
				</form>
EOD;
			$form = str_replace("\n", "", $form);
			$form = str_replace("\r", "", $form);
			$result["console"][]= $form;
		}
	}
	else
	{
		$title = nxs_tasks_gettaskstitle($refined_to_taskid);
		$result["console"][] = "You picked $title ($refined_to_taskid)";
		$result["console"][] = "Refine the qualification of this instance by creating a new instance of the refined qualification and by ending this instance";
		
		$block .= <<<EOD
			* [nxs_p001_task_instruction indent=1 type='create-task-instance' create_taskid='{$refined_to_taskid}' helpscoutthreadid='{$helpscoutthreadid}' happyflow_behaviour='end_task_instance;start_child_task_instance;redirect_to_child_instance']
			* (will automatically end this instance and redirect)
EOD;
		
		$lines = explode("\r\n", $block);
	
		foreach ($lines as $line)
		{
			$result["console"][] = do_shortcode($line);
		};
	}
	
	$result["console"][] = "";
	$result["console"][] = "--------";
	
	//$result["console"][] = json_encode($properties);
	
  $result["result"] = "OK";
  
  return $result;
}