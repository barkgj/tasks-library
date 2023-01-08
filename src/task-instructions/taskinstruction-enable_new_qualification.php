<?php

function nxs_task_instance_do_enable_new_qualification($then_that_item, $taskid, $taskinstanceid)
{
	$stickyparameters = nxs_task_getstickyparameters();
	$stickyparameters_html = implode(", ", $stickyparameters);
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];

	$qualification = $inputparameters["qualification"];
	$frequency = $inputparameters["frequency"];
	$messageformat = $inputparameters["messageformat"];
	$subject_original_ticket = $inputparameters["subject_original_ticket"];

	$task_title_template = $then_that_item["task_title_template"];
	$task_title_template = str_replace("'", "", $task_title_template);
	
	if ($task_title_template == "")
	{
		$msg = "nxs_task_instance_do_enable_new_qualification; task_title_template not set";
		$result["console"][] = $msg;
  	$result["result"] = "OK";
  	return $result;
	}
	
	// blend the template with inputparameters
	$applied_task_title_template = $task_title_template;
	$applied_task_title_template = nxs_filter_translatesingle($applied_task_title_template, "{{", "}}", $inputparameters);
	
	$result["console"][] = "<div style='background-color: #eee; border-color: #777; border-width: 3px; border-style: solid; padding: 5px;'>";
	
	$block = <<<EOD
Double-Check if the message is not a spam message
* Logic to apply; figure out all links used in the body of the message; check if those point to the domain of the vendor. If they point elsewhere thats suspicious and most likely spam.

[nxs_p001_task_instruction type='conditional_wrapper_begin' title='ALTERNATIVE FLOW - If it IS spam' id='altflow_isspam']	
* [nxs_p001_task_instruction type='create-task-instance' create_taskid=274 render_required_fields=true original_helpscoutticketnr='{{original_helpscoutticketnr}}' happyflow_behaviour='end_task_instance;start_child_task_instance;redirect_to_child_instance']
[nxs_p001_task_instruction type='conditional_wrapper_end']	

Write down how you would qualify this message in the broadest possible view
* [nxs_p001_task_instruction type='render-copy-to-clipboard' label='subject_original_ticket' value='{{subject_original_ticket}}']
* [nxs_p001_task_instruction indent=1 type='input-parameter' name='qualification']<br />

EOD;

	if ($qualification != "")
	{
		$block .= <<<EOD
			* <h1 style='display: inline-block;'>{$qualification}</h1>
			* [nxs_p001_task_instruction indent=1 type='require-parameter' name='qualification']<br />
EOD;

		$frequency_instruction = "[nxs_p001_task_instruction indent=1 type='input-parameter' name='frequency' inputtype='dropdown' items='RECURS=recurs;ONE_OF_A_KIND=one of a kind']";

		if (false)
		{
			//
		}
		else if ($frequency == "")
		{
			$block .= <<<EOD
			* Define the expected frequency of this message (if recurs use 'RECURS' or is this one of a kind use 'ONE_OF_A_KIND')
			** $frequency_instruction
EOD;
		}
		else if ($frequency == "RECURS")
		{
			$block .= <<<EOD
				* $frequency_instruction
				* You expect emails similar to this one to arrive on a recurring basis
				
EOD;
	
			$messageformat_instruction = "[nxs_p001_task_instruction indent=2 type='input_parameter' name='messageformat' inputtype='dropdown' items='STRUCTURED=structured;UNSTRUCTURED=unstructured']";
			
			if (false)
			{
				//
			}
			else if ($messageformat == "")
			{
				$block .= <<<EOD
					* Define the format of this message (if the subject and body are structured then use 'STRUCTURED' (this implies a script will be able to identify this qualification from now on), else use 'UNSTRUCTURED' (this will mean a human will have to identify this qualification manually)
					** {$messageformat_instruction}
EOD;
			}
			else if ($messageformat == "STRUCTURED")
			{
				$block .= <<<EOD
					* {$messageformat_instruction}
					* You believe the format of these types of email's are STRUCTURED
					** Identify what phrases in the subject uniquely qualifies these type of messages and distinguishes them from any other message
					*** Example are:
					**** Particular words in the subject line
					***** <span style='font-family: courier;'>{$subject_original_ticket}</span>
					***** If so, write each group of word that applies, seperate the groups with "^";
					****** [nxs_p001_task_instruction indent=6 type='require-parameter' name='subject_words_to_qualify']
					
					** If you want to pull a variable
					*** IF variable is located between structured strings
					
					
					**** Determine input parameter where variable to be pulled is located
					***** Most likely values to use for var_1_input are:
					****** [nxs_p001_task_instruction type='render-copy-to-clipboard' label='subject_original_ticket' value='subject_original_ticket']
					****** [nxs_p001_task_instruction type='render-copy-to-clipboard' label='message' value='message']
					**** [nxs_p001_task_instruction indent=3 type='require-parameter' name='var_1_input']
					**** Determine (part of) structured string before variable to be pulled (include space before variable)
					**** [nxs_p001_task_instruction indent=3 type='require-parameter' name='var_1_before']
					**** Determine (part of) structured string after variable to be pulled (include space after variable)
					**** [nxs_p001_task_instruction indent=3 type='require-parameter' name='var_1_after']
					**** Determine name of variable to be pulled (replace space(s) with underscore(s))
					**** [nxs_p001_task_instruction indent=3 type='require-parameter' name='var_1_name']
					
					*** IF variable is located after a structured string
					**** Determine (part of) structured string before variable to be pulled (include space before variable)
					**** [nxs_p001_task_instruction indent=3 type='require-parameter' name='var_1_grabafter_seperator']
					**** Determine name of variable to be pulled (replace space(s) with underscore(s))
					**** [nxs_p001_task_instruction indent=3 type='require-parameter' name='var_1_grabafter_name']
					
					
					** Identify the prio for handling this message
					*** [nxs_p001_task_instruction indent=3 type='require-parameter' name='message_prio']
					
					** Enter the input parameters of the current task that are needed to handle this qualification, seperate each input parameter with "^"
					** (note; spaces before or after each input parameter will be trimmed, so "a ^ b" would be equals to "a^b")
					** (note; sticky parameters that you dont have to include are; {$stickyparameters_html})
					*** [nxs_p001_task_instruction indent=3 type='require-parameter' name='inputparameters_to_clone']
					
					** Require a task to be made for handling these types of messages
					*** [nxs_p001_task_instruction indent=3 type='add_task' task_title_template='{$task_title_template}']
					*** If your receive a warning
					**** Please edit {{qualification}} and remove special characters
					
					* Run the 'workflow' of 'this' task
					** <a target='_blank' href='https://global.nexusthemes.com/api/1/prod/run-workflows-for-taskinstance/?nxs=businessprocess-api&nxs_json_output_format=prettyprint&taskid={{taskid}}&taskinstanceid={{taskinstanceid}}'>click here to run workflow</a>
					** Verify this task instance is now closed (the workflow should have created offspring and closed this instance)
					*** [nxs_p001_task_instruction type='reload_gui_current_page']	
EOD;
			}
			else if ($messageformat == "UNSTRUCTURED")
			{
				$block .= <<<EOD
					* {$messageformat_instruction}
					* You believe the format of these types of email's are UNSTRUCTURED
					** Require a task to be made for handling these types of messages
					*** [nxs_p001_task_instruction indent=3 type='add_task' task_title_template='{$task_title_template}']
					
					* Store the new taskid you just created to handle the qualification
					** [nxs_p001_task_instruction type='set_parameter' set_inputparameter_field='taskid_to_handle_qualification' function='ixplatform' schema='nxs.p001.businessprocess.task' ixplatform_filter='where_property=title where_operator=equals where_value="{$applied_task_title_template}"' not_found_behaviour='returnstatic:ERR_NOT_FOUND' multi_found_behaviour='returnstatic:ERR_MULT_FOUND' one_found_behaviour='returnfield' field_to_return='nxs.p001.businessprocess.task_id']
					
					* Extend possible qualifications for 'this' task with the task you just created
					** [nxs_p001_task_instruction indent=2 type='open-ixplatform-table' schema='nxs.p001.businessprocess.task' ]
					** Locate the row representing "this" task
					*** [nxs_p001_task_instruction type='render-copy-to-clipboard' label='The row with ID' value='{{taskid}}']	
					** Update that row
					*** Update colum <i>qualification_refinement_taskids</i>
					**** Append the taskid you just created
					***** [nxs_p001_task_instruction type='render-copy-to-clipboard' label='APPEND VALUE:' value='{{taskid_to_handle_qualification}}']	
					***** NOTE; use ";" as the seperator if there are already other task ids listed there
					** [nxs_p001_task_instruction type='commit-to-ixplatform' schema="nxs.p001.businessprocess.task"]	
					** [nxs_p001_task_instruction type='reload_gui_current_page']
					** Verify you now see the new task you just created to show up as a qualification refinement
					** Select that new qualification refinement for this task
					** Close this instance (probably the previous line should already close this instance)
EOD;
			}
			else
			{
				$block .= <<<EOD
				
					** TODO: unsupported messageformat; $messageformat
EOD;
			}
		}
		else if ($frequency == "ONE_OF_A_KIND")
		{
			$block .= <<<EOD
				$frequency_instruction
				ONE OF A KIND
				DECIDE TO:
				- SENT A CUSTOM REPLY
				* [nxs_p001_task_instruction indent='1' type='create-task-instance' create_taskid='431']
				- EITHER MAKE A PROJECT AND CLOSE 
				- ITS AN INCIDENT
				-- CREATE INCIDENT
				-- CLOSE THIS INSTANCE
				* [nxs_p001_task_instruction indent='1' type='end-task-instance']
				or
				- MAKE A SUMMARY OF THE MAIL AND CLOSE)
				* [nxs_p001_task_instruction type='require_textarea_parameter' name='summary' render_expanded_view_when_not_empty='true']
				* [nxs_p001_task_instruction indent='1' type='end-task-instance']
EOD;
		}
		else
		{
			$block .= <<<EOD
				FREQUENCY NOT YET SUPPORTED; {$frequency}
EOD;
		}
	}
	
	//
	//
	//	
	
	$lines = explode("\r\n", $block);
	
	foreach ($lines as $line)
	{
		$result["console"][] = do_shortcode($line);
	}
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$result["console"][] = "</div>";
	
  $result["result"] = "OK";
  
  return $result;
}