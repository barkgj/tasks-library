<?php

function nxs_task_instance_do_render_invoices_for_year($then_that_item, $taskid, $taskinstanceid)
{
	$marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$state = $instancemeta["state"];
	
	$result = array();
  
  $vendorid = $then_that_item["vendorid"];
  $year = $then_that_item["year"];
  
  $result["console"][] = "rendering invoices for vendor id {$vendorid} and year {$year}";
  
  if ($vendorid == "")
  {
  	$result["console"][] = "vendorid attribute not set";
  }
  else if ($year == "")
  {
  	$result["console"][] = "year attribute not set";
  }
  else
  {
  	$invoices_url = "https://global.nexusthemes.com/api/1/prod/list-purchase-invoices-for-year/?nxs=finance-api&nxs_json_output_format=prettyprint&year={$year}&vendorid={$vendorid}";
  	$invoices_string = file_get_contents($invoices_url);
  	$invoices_result = json_decode($invoices_string, true);
  	if ($invoices_result["result"] != "OK")
  	{
  		$result["console"][] = "error fetching invoices";
  	}
  	else
  	{
  		$count = $invoices_result["count"];
  		$invoices = $invoices_result["invoices"];
  		$result["console"][] = "found {$count} invoices";
  		foreach ($invoices as $invoiceid)
  		{
  			$viewinvoiceurl = "https://global.nexusthemes.com/?nxs=task-gui&page=viewinvoice&vendorid={$vendorid}&year={$year}&invoiceid={$invoiceid}";
  			$result["console"][] = "<a href='{$viewinvoiceurl}' target='_blank'>invoice: {$invoiceid}</a>";
  		}
  	}
  }
  
  $result["result"] = "OK";
  
  return $result;
}