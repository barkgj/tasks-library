<?php

function nxs_task_instance_do_assist_create_new_project($then_that_item, $taskid, $taskinstanceid)
{
	$block = <<<EOD
Sometimes a message arrives which requires action on our side in the form of a project.
One of the characteristics of a project is that its one-off (one of a kind).


'one of a kind' Should the message trigger a new project?
* If so
** Write down how you would qualify this message
*** [nxs_p001_task_instruction indent=3 type='require-parameter' name='qualification']
** Identify what phrases in the subject uniquely qualifies these type of messages and distinguishes them from any other message
*** Example are:
**** Particular words in the subject line
***** If so, write each group of word that applies, seperate the groups with "^";
****** [nxs_p001_task_instruction indent=6 type='require-parameter' name='subject_words_to_qualify']

** If a variable should be pulled from the subject between words, qualify the before and after and give this a name
*** [nxs_p001_task_instruction indent=3 type='require-parameter' name='var_1_before']
*** [nxs_p001_task_instruction indent=3 type='require-parameter' name='var_1_after']
*** [nxs_p001_task_instruction indent=3 type='require-parameter' name='var_1_name']

** If a variable should be pulled from the subject after a particular string, qualify the seperator and give this a name
*** [nxs_p001_task_instruction indent=3 type='require-parameter' name='var_1_grabafter_seperator']
*** [nxs_p001_task_instruction indent=3 type='require-parameter' name='var_1_grabafter_name']

** Identify the prio for handling this message
*** [nxs_p001_task_instruction indent=3 type='require-parameter' name='message_prio']

** Enter the input parameters of the current task that are needed to handle this qualification, seperate each input parameter with "^"
** (note; spaces before or after each input parameter will be trimmed, so "a ^ b" would be equals to "a^b")
*** [nxs_p001_task_instruction indent=3 type='require-parameter' name='inputparameters_to_clone']

** Require a task to be made for handling these types of messages
*** [nxs_p001_task_instruction indent=3 type='add_task' task_title_template='{$task_title_template}' prio='{$message_prio}']

** Write down the task id you just created
*** [nxs_p001_task_instruction indent=3 type='require-parameter' name='taskid_to_handle']

** Run the 'workflow' of 'this' task
*** <a target='_blank' href='https://global.nexusthemes.com/api/1/prod/run-workflows-for-taskinstance/?nxs=businessprocess-api&nxs_json_output_format=prettyprint&taskid={{taskid}}&taskinstanceid={{taskinstanceid}}'>click here to run workflow</a>
*** Verify it created task instance {{taskid_to_handle}}
** [nxs_p001_task_instruction indent=2 type='end-task-instance']	
EOD;
	
	$lines = explode("\r\n", $block);
	
	//var_dump($lines);
	//die();
	
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