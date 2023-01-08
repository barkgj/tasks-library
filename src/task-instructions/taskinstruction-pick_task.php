<?php

require_once("/srv/generic/libraries-available/nxs-tasks/nxs-tasks.php");

function nxs_task_instance_do_pick_task($then_that_item, $taskid, $taskinstanceid)
{
	$marker = $then_that_item["marker"];
	
	$field = $then_that_item["field"];
	if ($field == "")
	{
		$result["console"][] = "nxs_task_instance_do_pick_task; err; field attribute not set";
		$result["result"] = "OK";
		return $result;
	}
	
	$result["console"][] = "TASK PICKER ({$field})";
	$result["console"][] = "-----------";
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$value = $inputparameters[$field];
	if ($value == "")
	{
		$schema = "nxs.p001.businessprocess.task";
		global $nxs_g_modelmanager;
		$a = array
		(
			"singularschema" => $schema,
		);
		$allentries = $nxs_g_modelmanager->gettaxonomypropertiesofallmodels($a);
		
		foreach ($allentries as $entry)
		{
			$possibletaskid = intval($entry["nxs.p001.businessprocess.task_id"]);
			$taskmetabyid[$possibletaskid] = $entry;
		}
		
		ksort($taskmetabyid);
		foreach ($taskmetabyid as $possibletaskid => $entry)
		{	
			$id = $possibletaskid;	
			$title = $entry["title"];
			$title = "{$id} - {$title}";
			
			$currenturl = nxs_geturlcurrentpage();
			$returnurl = "{$currenturl}#{$marker}";
			$button_text = nxs_render_html_escape_singlequote("task: {$title}");
			
			$html = <<<EOD
				<form action='https://global.nexusthemes.com/' method='POST'>
					<input type='hidden' name='nxs' value='task-gui' />
					<input type='hidden' name='action' value='updateparameter' />
					<input type='hidden' name='page' value='taskinstancedetail' />
					<input type='hidden' name='taskid' value='{$taskid}' />
					<input type='hidden' name='taskinstanceid' value='{$taskinstanceid}' />
					<input type='hidden' name='name' value='{$field}' />
					<input type='hidden' name='value' value='{$id}' />
					<input type='hidden' name='returnurl' value='{$returnurl}' />
					<input type='submit' value='{$button_text}' style='background-color: #CCC;'>
				</form>
EOD;
			$html = str_replace("\n", "", $html);
			$html = str_replace("\r", "", $html);
			$result["console"][]= $html;
			$result["console"][]= "---------";
		}
	}
	else
	{
		$title = nxs_tasks_gettaskstitle($value);
		$result["console"][] = "TASK PICKED: $value ($title)";
		// allow user to pick a different one
		$result["console"][] = "";	// this is an empty line
		$result["console"][] = "TO RESET THE VALUE USE <div style='margin-left: 50px;'> " . do_shortcode("[nxs_p001_task_instruction type='set_parameter' set_inputparameter_field='{$field}' function='staticvalue' value='']") . "</div>";
	}
	
	$result["result"] = "OK";
	return $result;
}