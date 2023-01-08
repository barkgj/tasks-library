<?php

function nxs_task_instance_do_pick_sales_context($then_that_item, $taskid, $taskinstanceid)
{
	$result["console"][] = "PICK SALES CONTEXT";
	$result["console"][] = "-----------";
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$course_of_action = $inputparameters["course_of_action"];
	
	if (false)
	{
	}
	else if ($course_of_action == "")
	{
		$result["console"][] = "nxs_task_instance_do_pick_sales_context; PLEASE SELECT course_of_action FIRST :)";
	}
	else if ($course_of_action == "perform_sales")
	{
		$result["console"][] = "PICK THE PROPER LICENSE THAT APPLIES OUT OF THE FOLLOWING OPTIONS:";
		
		// pull items from ixplatform
		$schema = "nxs.sales.question";

		global $nxs_g_modelmanager;
		$items = $nxs_g_modelmanager->gettaxonomypropertiesofallmodels(array("singularschema" => $schema));
		foreach ($items as $item)
		{
			$id = $item["{$schema}_id"];
			$question = $item["question_en"];
			$persona = $item["persona"];
			if (nxs_stringcontains($persona, 'presale = true'))
			{
				$result["console"][] = "Option: {$id} - {$question}<br />";
				$button_text = "Pick {$id}";
				$result["console"][]= "<form action='https://global.nexusthemes.com/' method='POST'><input type='hidden' name='nxs' value='task-gui' /><input type='hidden' name='action' value='updateparameter' /><input type='hidden' name='page' value='taskinstancedetail' /><input type='hidden' name='taskid' value='{$taskid}' /><input type='hidden' name='taskinstanceid' value='{$taskinstanceid}' /><input type='hidden' name='name' value='question_id' /><input type='hidden' name='value' value='{$id}' /><input type='hidden' name='returnurl' value='{$returnurl}' /><input type='submit' value='{$button_text}' style='background-color: #CCC;'></form>";
				$result["console"][]= "---------";
			}
		}
	}
	else
	{
		$result["console"][] = "UNSUPPORTED COURSE OF ACTION: $course_of_action :)";
	}
	
	$result["result"] = "OK";
	return $result;
}