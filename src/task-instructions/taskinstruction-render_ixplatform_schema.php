<?php

function nxs_task_instance_do_render_ixplatform_schema($then_that_item, $taskid, $taskinstanceid)
{
	// $marker = $then_that_item["marker"];
	
	//$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	//$inputparameters = $instancemeta["inputparameters"];
	
	// $state = $instancemeta["state"];
	
  $result = array();
  
  //
  //
  //
  
  $schema = $then_that_item["schema"];

	global $nxs_g_modelmanager;
	$items = $nxs_g_modelmanager->gettaxonomypropertiesofallmodels(array("singularschema" => $schema));
	
	
	
	$html = "";
	
	$html .= "<table class='table-oddeven'>";
	// header row (columns)
	foreach ($items as $item)
	{
		$html .= "<thead>";
		$html .= "<tr>";
		foreach ($item as $key => $val)
		{
			$html .= "<th>{$key}</th>";	
		}
		$html .= "</tr>";
		$html .= "</thead>";
		break;
	}
	// data rows
	$html .= "<tbody>";
	foreach ($items as $item)
	{
		$html .= "<tr>";
		foreach ($item as $key => $val)
		{
			$html .= "<td>{$val}</td>";	
		}
		$html .= "</tr>";
	}
	$html .= "</tbody>";
	$html .= "</table>";

  $result["console"][] = "render ixplatform {$schema}";
  $result["console"][] = do_shortcode("[nxs_p001_task_instruction type='open-ixplatform-table' schema='{$schema}']");
  $result["console"][] = $html;
  
  $result["result"] = "OK";
  
  return $result;
}