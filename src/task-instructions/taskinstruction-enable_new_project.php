<?php

function nxs_task_instance_do_enable_new_project($then_that_item, $taskid, $taskinstanceid)
{
	$result = array();

	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];

	$result["console"][] = "<div style='background-color: #eee; border-color: #777; border-width: 3px; border-style: solid; padding: 5px;'>";
	
	$result["console"][] = "If message requires activities to be done on our side which are one of a kind (project-based)?";
	$result["console"][] = "* If so";
	
	$project = $inputparameters["project"];
	$project = str_replace("'", "", $project);
	
	$original_helpscoutticketnr = $inputparameters["original_helpscoutticketnr"];
	$subject_original_ticket = $inputparameters["subject_original_ticket"];
	
	if ($project == "")
	{
		$result["console"][] = "** Write down how you would qualify this project";
		$result["console"][] = "*** [nxs_p001_task_instruction indent=3 type='require-parameter' name='project']";
	}
	else
	{
		$subconditions = array();
		
		// check if project exists
		$subconditions[] = array
		(
			"type" => "true_if_task_has_required_taskid",
			"required_taskid" => "252",
		);
		$subconditions[] = array
		(
			"type" => "true_if_inputparameter_has_required_value_for_key",
			"key" => "project",
			"required_value" => $project,
		);
		
		$search_args = array
		(
			"if_this" => array
			(
				"type" => "true_if_each_subcondition_is_true",
				"subconditions" => $subconditions,
			),
		);
		
		$taskinstances_wrap = nxs_tasks_searchtaskinstances($search_args);
		$taskinstances = $taskinstances_wrap["taskinstances"];
		
		if (count($taskinstances) == 0)
		{
			$result["console"][] = "** Create a 'do project' task instance)";
			$result["console"][] = "*** [nxs_p001_task_instruction indent=3 type='create-task-instance' create_taskid=252 project='{$project}' original_helpscoutticketnr='{$original_helpscoutticketnr}' subject_original_ticket='{$subject_original_ticket}']";
			
			$urlcurrentpage = nxs_geturlcurrentpage();
	  	$next_url = $urlcurrentpage;
			$next_url = nxs_addqueryparametertourl_v2($next_url, "t", time(), true, true);
	  	$next_url = "{$next_url}#doproject";	// refreshes current page
			
			$result["console"][] = "** <a href='{$next_url}'>Refresh page to find the project instance</a>";
		}
		else
		{
			$result["console"][] = "** <span id='doproject'>Do the project</span>";
			$result["console"][] = "*** [nxs_p001_task_instruction indent=3 type='render_task_instances_v2' f_taskid=252 f1_key=project f1_op=equals f1_equals='{$project}']";
			$result["console"][] = "*** [nxs_p001_task_instruction indent=3 type='end-task-instance']";
		}
	}
	
	$result["console"][] = "</div>";
	
	// process all shortcodes
	foreach ($result["console"] as $index => $line)
	{
		$result["console"][$index] = do_shortcode($line);
	}
	
  $result["result"] = "OK";
  
  return $result;
}