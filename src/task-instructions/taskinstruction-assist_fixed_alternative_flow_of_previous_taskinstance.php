<?php

require_once("/srv/generic/libraries-available/nxs-workflows/nxs-workflows.php");

function nxs_task_instance_do_assist_fixed_alternative_flow_of_previous_taskinstance($then_that_item, $taskid, $taskinstanceid)
{
	$marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$state = $instancemeta["state"];
	
  $result = array();
  
  if ($state == "STARTED")
  {
  	$message = $inputparameters["message"];
  	$references = nxs_task_getreferencedtaskinstances($message);
  	if (count($references) > 0)
  	{
  		$result["console"][] = "<div style='background-color: #DDA0DD;'>begin of assist_fixed_alternative_flow_of_previous_taskinstance<br />it looks like this task instance was created as a response of the client to a mail sent by a previous task. Should you have the need to re-create an instance based on that previous task (for example  we need to receive a corrected password, user provides the corrected password<br />";
  		
			foreach ($references as $reference)
			{
				$ref_taskid = $reference["taskid"];
				$ref_taskinstanceid = $reference["taskinstanceid"];
				
				$linkurl = "https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid={$ref_taskid}&taskinstanceid={$ref_taskinstanceid}";
				$result["console"][] = "hint: references $ref_taskid $ref_taskinstanceid (<a target='_blank' href='$linkurl'>link</a>)";
				
				if ($ref_taskid == 33)
				{
					$ref_args = array();
					$stacktrace = nxs_task_getstacktrace($ref_taskid, $ref_taskinstanceid, $ref_args);
					$stacktraceitem = $stacktrace[count($stacktrace) - 1];
	
					$createdby_taskid = $stacktraceitem["taskid"];
					$createdby_taskinstanceid = $stacktraceitem["taskinstanceid"];
					$title = nxs_tasks_gettaskstitle($createdby_taskid);
					
					$result["console"][] = "hint: created instance of id: $createdby_taskid ($title) $createdby_taskinstanceid";
					$ref_k_to_skip = array("message");
					foreach ($stacktraceitem["inputparameters"] as $ref_k => $ref_v)
					{
						if (in_array($ref_k, $ref_k_to_skip))
						{
							continue;
						}
						$ref_v = str_replace("\\", "\\\\", $ref_v);	// escape 'm
						
						// $result["console"][] = do_shortcode("[nxs_p001_task_instruction type='render-copy-to-clipboard' label='{$ref_k}' value='{$ref_v}']");
					}
					
					$workflow_state = nxs_task_getworkflowsavailabilitystate_for_task($createdby_taskid);
					if (false)
					{
						//
					}
					else if ($workflow_state == "NOTFOUND")
					{
						$result["console"][] = do_shortcode("[nxs_p001_task_instruction type='create-task-instance' create_taskid='{$createdby_taskid}' render_required_fields='true' overrule_allowcreation='true' happyflow_behaviour='end_task_instance;reload_gui_current_page']");
					}
					else if ($workflow_state == "AVAILABLE" || $workflow_state == "BROKEN")
					{
						$result["console"][] = do_shortcode("Unable to (re)create an instance of the previous task as it was a task that runs automated (workflow); recreating would cause the user to receive the same email to which the reply related to");
					}
					else
					{
						$result["console"][] = do_shortcode("Unexpected workflow state; $workflow_state");
					}
				}
				else
				{
					// not expected ?
				}
			}
			
			$result["console"][] = "end of assist_fixed_alternative_flow_of_previous_taskinstance</div>";
		}
		else
		{
			$result["console"][] = "assist_fixed_alternative_flow_of_previous_taskinstance; no references found";
		}	  
  }
  else
  {
  	$result["console"][] = "assist_fixed_alternative_flow_of_previous_taskinstance; nothing to do";
  }
  
  
  
  //
  //
  //
  
  $result["result"] = "OK";
  
  return $result;
}