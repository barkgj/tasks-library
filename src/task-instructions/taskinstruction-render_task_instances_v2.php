<?php

function nxs_task_instance_do_render_task_instances_v2($then_that_item, $taskid, $taskinstanceid)
{
	$result = array();

	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];

	// $result["console"][] = "<span id='add_task_marker'>add task marker</span";
	// $x = nxs_addqueryparametertourl_v2($x, "foo", $bar, true, true);
	// $result["console"][] = "*** Destination folder: /srv/metamodel/task.recipes";
	// $x = nxs_filter_translatesingle($x, "{{", "}}", $inputparameters);
	
	/*
  if ($key == "")
  {
  	$msg = "key not set";
  	$result["console"][] = $msg;
  	$result["nack_message"] = $msg;
  	$result["result"] = "NACK";
  	
  	return $result;
  }
  */
  
  // construct the search arguments
  if (true)
  {
	  $subconditions = array();
	  
	  $f_states = $then_that_item["f_states"];
	  if ($f_states != "")
	  {
	  	$subconditions[] = array
			(
				"type" => "true_if_in_any_of_the_required_states",
				"any_of_the_required_states" => explode("|", $f_states),
			);
	  }
	  
	  $f_exclude_current = $then_that_item["f_exclude_current"];
	  if ($f_exclude_current == "true")
	  {
	  	$subconditions[] = array
			(
				"type" => "true_if_not_current"
			);
	  }
	  
		$f_taskid = $then_that_item["f_taskid"];
	  if ($f_taskid != "")
	  {
	  	$subconditions[] = array
			(
				"type" => "true_if_task_has_required_taskid",
				"required_taskid" => $f_taskid
			);
	  }
	  
	  $f1_key = $then_that_item["f1_key"];
	  $f1_op = $then_that_item["f1_op"];
	  $f1_equals = $then_that_item["f1_equals"];
	  $f1_equals = nxs_filter_translatesingle($f1_equals, "{{", "}}", $inputparameters);
	  if ($f1_key != "")
	  {
	  	if ($f1_op == "equals")
	  	{
	  		$subconditions[] = array
				(
					"type" => "true_if_inputparameter_has_required_value_for_key",
					"key" => $f1_key,
					"required_value" => $f1_equals
				);
	  	}
	  	else
	  	{
	  		// not supported
	  	}
	  	
	  }
	  
	  $search_args = array
		(
			"if_this" => array
			(
				"type" => "true_if_each_subcondition_is_true",
				"subconditions" => $subconditions,
			),
		);
	}
	$taskinstances_wrap = nxs_tasks_searchtaskinstances($search_args);
	$taskinstances = $taskinstances_wrap["taskinstances"];
	
	$entries = array();
	foreach ($taskinstances as $taskinstance)
	{
		$taskid = $taskinstance["taskid"];
		$title = nxs_tasks_gettaskstitle($taskid);
		$taskinstanceid = $taskinstance["taskinstanceid"];
		$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
		
		$entry["taskid"] = $taskid;
		$entry["tasktitle"] = $title;
		$entry["taskinstanceid"] = $taskinstanceid;
		$entry = array_merge($entry, $instancemeta);
		
		$entries[] = $entry;
	}
	
	$orderby = $then_that_item["orderby"];
	if ($orderby == "")
	{
		$orderby = "createtime";
	}
	
	// order by the given column
	usort($entries, function ($item1, $item2) use ($orderby)
	{
    return $item1[$orderby] <=> $item2[$orderby];
	});
	
	$html .= "<table class='rendertaskinstancesv2'>";
	$html .= "<tr>";
	$html .= "<td>Task id</td>";
	$html .= "<td>Title</td>";
	$html .= "<td>State</td>";
	$html .= "<td>Process type</td>";
	$html .= "<td>Create time</td>";
	$html .= "<td>ID</td>";
	$html .= "<td>Input parameters</td>";
	$html .= "</tr>";
	foreach ($entries as $entry)
	{
		
		$createtime = $entry["createtime"];
		$createtime_html = "";
		$createtime_html .= date("Ymd H:i:s", $createtime);
		
		$state = $entry["state"];
		if ($state == "ABORTED")
		{
			$abort_reason = $entry["abort_reason"];
			$abort_note = $entry["abort_note"];
			
			$state_html = $state . "<br />{$abort_reason}<br />{$abort_note}";
		}
		else
		{
			$state_html = $state;
		}
		
		
		
		$taskid = $entry["taskid"];
		$processtype_html = nxs_tasks_getprocessingtype($taskid);
		
		$title = $entry["tasktitle"];
		$taskinstanceid = $entry["taskinstanceid"];
		
		$action_url = "https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid={$taskid}&taskinstanceid={$taskinstanceid}";
		
		// output settings; determines which inputparameters to output
		$o_inputparameters = $then_that_item["o_inputparameters"];
		$o_inputparameters_list = explode(";", $o_inputparameters);
		
		$inputparameters = $entry["inputparameters"];
		$parameters_html = "";
		foreach ($inputparameters as $inputparameter => $val)
		{
			$shouldshow = true;
			if ($o_inputparameters != "")
			{
				if (!in_array($inputparameter, $o_inputparameters_list))
				{
					$shouldshow = false;
				}
			}
			
			if ($shouldshow)
			{
				
				$parameters_html .= "{$inputparameter} : {$val}<br />";
			}
		}
		
		$html .= "<tr class='state-{$state}'>";
		$html .= "<td>{$taskid}</td>";
		$html .= "<td>{$title}</td>";
		$html .= "<td>{$state_html}</td>";
		$html .= "<td>{$processtype_html}</td>";
		$html .= "<td>{$createtime_html}</td>";
		$html .= "<td><a target='_blank' href='{$action_url}'>{$taskinstanceid}</a></td>";
		$html .= "<td>$parameters_html</td>";
		$html .= "</tr>";
	}
	$html .= "</table>";
	
	$more = <<<EOD
	<style>
	.rendertaskinstancesv2{
		width:100%; 
		border-collapse:collapse; 
	}
	.rendertaskinstancesv2 td{ 
		padding:7px; border:#4e95f4 1px solid;
	}
	.rendertaskinstancesv2 tr{
		background: #b8d1f3;
	}
	.rendertaskinstancesv2 tr:nth-child(odd){ 
		background: #b8d1f3;
	}
	.rendertaskinstancesv2 tr:nth-child(even){
		background: #dae5f4;
	}
	.rendertaskinstancesv2 tr
	{
		opacity: 0.5;
	}
	.rendertaskinstancesv2 tr.state-CREATED,
	.rendertaskinstancesv2 tr.state-STARTED
	{
		opacity: 1.0;
	}
	</style>
EOD;
	$more = str_replace("\n", "", $more);
	$more = str_replace("\r", "", $more);
	$html .= $more;
	
	$count = count($entries);
	// $result["console"][] = "filter: " . json_encode($search_args);
	$result["console"][] = "found {$count} task instance(s), order by {$orderby}:";
	$result["console"][] = "---------";
	$result["console"][] = $html;
	$result["console"][] = "---------";
	// $result["console"][] = "result: " . json_encode($taskinstances);
	
  $result["result"] = "OK";
  
  return $result;
}