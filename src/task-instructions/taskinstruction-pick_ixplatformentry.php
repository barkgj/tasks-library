<?php

require_once("/srv/generic/libraries-available/nxs-tasks/nxs-tasks.php");

function nxs_task_instance_do_pick_ixplatformentry($then_that_item, $taskid, $taskinstanceid)
{
	$marker = $then_that_item["marker"];

	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$ixplatform_filter = $then_that_item["ixplatform_filter"];
	if ($ixplatform_filter != "")
	{
		// apply inputparameters
		$ixplatform_filter = nxs_filter_translatesingle($ixplatform_filter, "{{", "}}", $inputparameters);
		
		if (nxs_stringcontains($ixplatform_filter, "{"))
		{
			$result["console"][] = "nxs_task_instance_do_pick_ixplatformentry; err; ixplatform_filter still has unreplaced placeholders; $ixplatform_filter";
			$result["result"] = "OK";
			return $result;
		}
	}
	
	$schema = $then_that_item["schema"];
	if ($schema == "")
	{
		$result["console"][] = "nxs_task_instance_do_pick_ixplatformentry; err; schema attribute not set";
		$result["result"] = "OK";
		return $result;
	}
	
	$inputparameterfield = $then_that_item["inputparameterfield"];
	if ($inputparameterfield == "")
	{
		$result["console"][] = "nxs_task_instance_do_pick_ixplatformentry; err; inputparameterfield attribute not set";
		$result["result"] = "OK";
		return $result;
	}
	
	$humantitle_field = $then_that_item["humantitle_field"];
	if ($humantitle_field == "")
	{
		$result["console"][] = "nxs_task_instance_do_pick_ixplatformentry; err; humantitle_field attribute not set";
		$result["result"] = "OK";
		return $result;
	}
	
	$result["console"][] = "{$schema} PICKER ({$inputparameterfield})";
	$result["console"][] = "-----------";
	$result["console"][] = do_shortcode("* [nxs_p001_task_instruction indent=1 type='open-ixplatform-table' schema='{$schema}']");
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$value = $inputparameters[$inputparameterfield];
	if ($value == "")
	{
		$q = "[nxsstring ops='listmodeluris' singularschema='{$schema}' {$ixplatform_filter}]";
		$matchingmodeluris = do_shortcode($q);
		$matchingmodeluri_list = explode(";", $matchingmodeluris);
		
		$allentries = array();
		global $nxs_g_modelmanager;
		foreach ($matchingmodeluri_list as $matchingmodeluri)
		{
			$a = array
			(
				"modeluri" => $matchingmodeluri,
			);
			$entry = $nxs_g_modelmanager->getmodeltaxonomyproperties($a);
			$allentries[] = $entry;
		}
			
		foreach ($allentries as $entry)
		{
			$possibleentryid = intval($entry["{$schema}_id"]);
			$metabyid[$possibleentryid] = $entry;
		}
		
		$count = count($metabyid);
		$result["console"][]= "* Pick one of the following {$count} entries:";
		
		ksort($metabyid);
		foreach ($metabyid as $possibleentryid => $entry)
		{	
			$id = $possibleentryid;	
			$title = $entry[$humantitle_field];
			
			if ($title == "") { $title = "empty (no value for column $humantitle_field)"; }
			
			$title = "{$id} - {$title}";
			
			$currenturl = nxs_geturlcurrentpage();
			$returnurl = "{$currenturl}#{$marker}";
			$button_text = nxs_render_html_escape_singlequote("{$title}");
			
			$html = <<<EOD
				<div style='margin-left: 100px;'>
					<form action='https://global.nexusthemes.com/' method='POST'>
						<input type='hidden' name='nxs' value='task-gui' />
						<input type='hidden' name='action' value='updateparameter' />
						<input type='hidden' name='page' value='taskinstancedetail' />
						<input type='hidden' name='taskid' value='{$taskid}' />
						<input type='hidden' name='taskinstanceid' value='{$taskinstanceid}' />
						<input type='hidden' name='name' value='{$inputparameterfield}' />
						<input type='hidden' name='value' value='{$id}' />
						<input type='hidden' name='returnurl' value='{$returnurl}' />
						<input type='submit' value='{$button_text}' style='background-color: #CCC;'>
					</form>
				</div>
EOD;
			$html = str_replace("\n", "", $html);
			$html = str_replace("\r", "", $html);
			$result["console"][]= $html;
			//$result["console"][]= "---------";
		}
	}
	else
	{
		global $nxs_g_modelmanager;
		$args = array("modeluri" => "{$value}@{$schema}");
		$task_props = $nxs_g_modelmanager->getmodeltaxonomyproperties($args);
	
		$title = $task_props[$humantitle_field];
		
		//
		$currenturl = nxs_geturlcurrentpage();
		$returnurl = $currenturl . "#{$marker}";

		$html .= "<form  style='display: inline-block;' action='https://global.nexusthemes.com/' method='POST'>";
		$html .= "<input type='hidden' name='nxs' value='task-gui' />";
		$html .= "<input type='hidden' name='action' value='updateparameter' />";
		$html .= "<input type='hidden' name='page' value='taskinstancedetail' />";
		$html .= "<input type='hidden' name='taskid' value='{$taskid}' />";
		$html .= "<input type='hidden' name='taskinstanceid' value='{$taskinstanceid}' />";
		$html .= "<input type='hidden' name='name' value='{$inputparameterfield}' />";
		$html .= "<input type='hidden' name='value' value='' />";
		$html .= "<input type='hidden' name='returnurl' value='{$returnurl}' />";
		$html .= "<input type='submit' value='Reset {$inputparameterfield}' style='background-color: #CCC;'>";
		$html .= "</form>";
		//

		$result["console"][] = "* PICKED {$schema}: {$value} ($title) ($html)";
	}
	
	$result["result"] = "OK";
	return $result;
}