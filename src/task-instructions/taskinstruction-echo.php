<?php

function nxs_task_instance_do_echo($then_that_item, $taskid, $taskinstanceid)
{
	$marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$state = $instancemeta["state"];

  $result = array();
  
	if (!nxs_tasks_isheadless())
	{
		global $nxs_gl_recipe_instruction_pointer;
		
		$linenr = $nxs_gl_recipe_instruction_pointer["linenr"];
	}
	
	if (false)
	{
	}
	else if ($state == "STARTED")
	{
	  $function = $then_that_item["function"];
	  if (false)
	  {
	  	//
	  }
	  else if ($function == "shortcode")
	  {
	  	$shortcode_type = $then_that_item["shortcode_type"];
	  	$shortcode_input = $then_that_item["shortcode_input"];
	  	$shortcode_input = nxs_filter_translatesingle($shortcode_input, "{{", "}}", $inputparameters);
	  	
			$moreshortcodeatts = "";
			$exclude_keys = array("shortcode_input", "shortcode_type", "type", "set_inputparameter_field", "type");
			foreach ($then_that_item as $k => $v)
			{
				if (!in_array($k, $exclude_keys))
				{				
					$pimped_v = $v;
					$pimped_v = nxs_filter_translatesingle($pimped_v, "{{", "}}", $inputparameters);
					
					$moreshortcodeatts .= "{$k}='{$pimped_v}' ";
				}
			}

			$shortcode = "[{$shortcode_type} value='{$shortcode_input}' $moreshortcodeatts]";
			if ($then_that_item["debug"] == "true")
			{
				$result["console"][] = "applying shortcode; $shortcode";
			}
			
			$value = do_shortcode($shortcode);
			$existing_value = $inputparameters[$set_inputparameter_field];
			
	  	if (nxs_tasks_isheadless())
			{
				$result["console"][] = "echoing value $value for $set_inputparameter_field";
				//
				// nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, $set_inputparameter_field, $value);
			}
			else
			{
				if (true)
				{
					$render_copy_to_clipboard = true;
					// if should wrap in clipboard
					
					$result["console"][] = "{$value}";
					
					if ($render_copy_to_clipboard)
					{
						// replace \r\n's with <br />
						$value = str_replace("\r\n", "<br />", $value);
						$value = str_replace("\n", "<br />", $value);
						$value = str_replace("\r", "<br />", $value);
						
						$clipboardvalue = $value;
						$clipboardvalue = str_replace("&#92;", "\\", $clipboardvalue);
						$clipboardvalue = str_replace("'", "&apos;", $clipboardvalue);
						$clipboardvalue = str_replace("\\", "\\\\", $clipboardvalue);
						
						$label = "clipboard";
						
					  $result["console"][] = "<a href='#' onclick='navigator.clipboard.writeText(\"{$clipboardvalue}\"); return false;'>copy</a> {$label}: <i style='font-family: courier;'>$value</i>";
					}
				}
				
			}
	  }
	  else
	  {
	  	$result["nackdetails"] = "nxs_task_instance_do_echo; unsupported function; $function";
	  	$result["result"] = "NACK";
	  	return $result;
	  }
	}
	else
	{
		$result["console"][] = "wont do anything here because of state $state";
	}
	  
  $result["result"] = "OK";
	
  return $result;
}