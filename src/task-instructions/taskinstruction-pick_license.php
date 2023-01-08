<?php

function nxs_task_instance_do_pick_license($then_that_item, $taskid, $taskinstanceid)
{
	$result["console"][] = "PICK LICENSE";
	$result["console"][] = "-----------";
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$licenseid = $inputparameters["licenseid"];
	if ($licenseid == "")
	{
		$result["console"][] = "PICK THE PROPER LICENSE THAT APPLIES OUT OF THE FOLLOWING OPTIONS:";
		
		$non_expired_licenses = $inputparameters["non_expired_licenses"];
		$pieces = explode(";", $non_expired_licenses);
		foreach ($pieces as $piece)
		{
			// 
			$fetch_url = "https://license1802.nexusthemes.com/api/1/prod/licenseinsights/?nxs=licensemeta-api&nxs_json_output_format=prettyprint&licensenr={$piece}";
			$fetch_string = file_get_contents($fetch_url);
			$fetch_result = json_decode($fetch_string, true);
			$sitedomain = $fetch_result["license_data"]["sitedomain"];
			
			$currenturl = nxs_geturlcurrentpage();
			$returnurl = $currenturl;	//  . "#{$marker}"; marker is not available here, yet ...
			$button_text = nxs_render_html_escape_singlequote("licenseid: {$piece}");
			$result["console"][]= "active licenseid: {$piece} (sitedomain: $sitedomain)";
			$result["console"][]= "<form action='https://global.nexusthemes.com/' method='POST'><input type='hidden' name='nxs' value='task-gui' /><input type='hidden' name='action' value='updateparameter' /><input type='hidden' name='page' value='taskinstancedetail' /><input type='hidden' name='taskid' value='{$taskid}' /><input type='hidden' name='taskinstanceid' value='{$taskinstanceid}' /><input type='hidden' name='name' value='licenseid' /><input type='hidden' name='value' value='{$piece}' /><input type='hidden' name='returnurl' value='{$returnurl}' /><input type='submit' value='{$button_text}' style='background-color: #CCC;'></form>";
			$result["console"][]= "---------";
		}
		
		$expiredlicenses = $inputparameters["expiredlicenses"];
		$pieces = explode(";", $expiredlicenses);
		foreach ($pieces as $piece)
		{
			// 
			$fetch_url = "https://license1802.nexusthemes.com/api/1/prod/licenseinsights/?nxs=licensemeta-api&nxs_json_output_format=prettyprint&licensenr={$piece}";
			$fetch_string = file_get_contents($fetch_url);
			$fetch_result = json_decode($fetch_string, true);
			$sitedomain = $fetch_result["license_data"]["sitedomain"];
			
			$currenturl = nxs_geturlcurrentpage();
			$returnurl = $currenturl;	//  . "#{$marker}"; marker is not available here, yet ...
			$button_text = nxs_render_html_escape_singlequote("licenseid: {$piece}");
			$result["console"][]= "expired licenseid: {$piece} (sitedomain: $sitedomain)";
			$result["console"][]= "<form action='https://global.nexusthemes.com/' method='POST'><input type='hidden' name='nxs' value='task-gui' /><input type='hidden' name='action' value='updateparameter' /><input type='hidden' name='page' value='taskinstancedetail' /><input type='hidden' name='taskid' value='{$taskid}' /><input type='hidden' name='taskinstanceid' value='{$taskinstanceid}' /><input type='hidden' name='name' value='licenseid' /><input type='hidden' name='value' value='{$piece}' /><input type='hidden' name='returnurl' value='{$returnurl}' /><input type='submit' value='{$button_text}' style='background-color: #CCC;'></form>";
			$result["console"][]= "---------";
		}
		
	}
	else
	{
		$result["console"][] = "LICENSE PICKED: $licenseid :)";
	}
	
	$result["result"] = "OK";
	return $result;
}