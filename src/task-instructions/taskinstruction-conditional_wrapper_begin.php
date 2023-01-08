<?php

function nxs_task_instance_do_conditional_wrapper_begin($then_that_item, $taskid, $taskinstanceid)
{
	$marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	// $state = $instancemeta["state"];
	
	$title = $then_that_item["title"];
	if ($title == "")
	{
		$title = "title fallback for collapsible wrapper";
	}
	
	$condition = $then_that_item["condition"];
	
	if ($condition == "")
	{
		$condition = "gui";
	}
	
	if (false)
	{
	}
	else if ($condition == "gui")
	{
		$id = $then_that_item["id"];
		$id = strtolower($id);
		$id = str_replace(" ", "_", $id);
		$id = str_replace("-", "_", $id);
		
		if ($id == "")
		{
			$id = md5($title);
		}
		
		$state_key = "cond_wrap_state_{$id}";
		$condition_state = $inputparameters[$state_key];
	}
	else if ($condition == "shortcode")
	{
		// condition is derived at runtime based upon the condition as specified in the
		// shortcode attribute
		$shortcode = $then_that_item["shortcode"];
		// TODO: apply parameters from task instance if the shortcode contains placeholders
		// $condition_state = do_shortcode($shortcode);
		$condition_state = "TODO";
	}
	
	$displayatt = "";
	if ($condition_state == "" || $condition_state == "false")
	{
		$displayatt = "display: none";
	}
	else
	{
		$displayatt = "";
	}
	
  $result = array();
  
  $html = "";
  $html .= "<div class='collapsiblewrapper'>";
 
 	if ($condition == "gui")
 	{
 		// allow user to control the state
	  $html .= "<a href='#' onclick='nxs_toggle_{$id}(this); return false;'>";
	  $html .= "<script>";
	  $html .= "function nxs_toggle_{$id}(e) {";
	  // toggle the ajax part
	  $html .= "jQuery(e).closest('.collapsiblewrapper').find('.collapsible').toggle();";
	  $html .= "var newstate = jQuery(e).closest('.collapsiblewrapper').find('.collapsible').is(':visible'); ";
	  // invoke async call to set the state on the server for the task instance
	  $html .= "console.log('newstate;' + newstate);";
	  $html .= "var url = 'https://global.nexusthemes.com/api/1/prod/set-task-instance-input-parameter/?nxs=businessprocess-api&nxs_json_output_format=prettyprint&taskid={$taskid}&taskinstanceid={$taskinstanceid}&key={$state_key}&val=' + newstate;";
	  
	  $html .= "var request = {";
	  //$html .= "  foo: '{$bar}'";
	  $html .= "};";
	  $html .= "$.ajax({";
	  $html .= "  url: url,";
	  $html .= "  data: request,";
	  $html .= "  dataType: 'json',";
	  $html .= "  type: 'GET'";
	  $html .= "}).done(function(data) {";
		$html .= "console.log(data);";
		$html .= "});";
	  
	  $html .= "}";
	  $html .= "</script>";
	  $html .= "<h2>{$title}</h2>";
	  $html .= "</a>";
	}
	  
  $html .= "<div style='{$displayatt}' class='collapsible'>";
  // $html .= "<div>";	// not sure why this additional div is needed, but else it wont work

  $result["console"][] = $html;
  
  $result["result"] = "OK";
  
  return $result;
}