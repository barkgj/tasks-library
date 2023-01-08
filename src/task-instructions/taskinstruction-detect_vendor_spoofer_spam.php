<?php

function nxs_task_instance_do_detect_vendor_spoofer_spam($then_that_item, $taskid, $taskinstanceid)
{
	// $marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
  $result = array();
  
  $message = $inputparameters["message"];
  
  $is_likely_spoofed = false;
  
  if (false)
  {
  	//
  }
  else if (nxs_stringcontains($message, ".amazon.com."))
  {
		$is_likely_spoofed = true;
  }
  else if (nxs_stringcontains($message, "aws.amazon.login"))
  {
		$is_likely_spoofed = true;
  }
  
  
  if ($is_likely_spoofed)
  {
  	$result["console"][] = "<span style='font-size: 200%; background-color: red; color: white;'>WARNING, MOST LIKELY SPOOFED (SPAM)</span>";
  }
  else
  {
  	$result["console"][] = "no spoofing detected";
  }
  
  //
  //
  //
  
  $result["result"] = "OK";
  
  return $result;
}