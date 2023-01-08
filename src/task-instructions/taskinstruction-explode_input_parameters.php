<?php

function nxs_task_instance_do_explode_input_parameters($then_that_item, $taskid, $taskinstanceid)
{
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$result = array();
	
	$how = $then_that_item["how"];
	if ($how == "")
	{
		$how = "phpexplode";
	}
	
	$state = $instancemeta["state"];
	
  if ($state == "STARTED")
  {
  	$input = $then_that_item["input"];
  	$input = nxs_filter_translatesingle($input, "{{", "}}", $inputparameters);
  	
  	if (false)
  	{
  		//
  	}
  	else if ($how == "phpexplode")
  	{
	  	$atts = shortcode_parse_atts($input);
	  	
	  	$result["console"][] = "Exploding $input to:";
	  	
	  	//$result["console"][] = json_encode($atts);
	  	foreach ($atts as $key => $val)
	  	{
		  	if (!nxs_tasks_isheadless())
	  		{		
		  		$result["console"][] = do_shortcode("[nxs_p001_task_instruction type='set_parameter' set_inputparameter_field='{$key}' function='staticvalue' value='{$val}']");
		  	}
		  	else
		  	{
		  		$result["console"][] = "Storing $key $val";
		  		nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, $key, $val);
		  	}
		  }
		}
		else if ($how == "modeluris")
		{
			global $nxs_g_modelmanager;
			
			$modeluri_list = explode(";", $input);
			// input = "122@nxs.divithemes.itemmeta;1918@nxs.divithemes.itemmeta"
			// becomes
			// theme_3_id
			$keypattern = $then_that_item["keypattern"];
			if ($keypattern == "")
			{
				$keypattern = "exploded_(index)_id";
			}
			if (!nxs_stringcontains($keypattern, "(index)"))
			{
				$keypattern .= "_(index)";
			}
			
			$startindex = $then_that_item["startindex"];
			if ($startindex == "")
			{
				$startindex = 0;
			}
			$index = $startindex;

			foreach ($modeluri_list as $modeluri)
			{
				$humanid = $nxs_g_modelmanager->gethumanid($modeluri);	// for example 122

				$key = $keypattern;
				$key = str_replace("(index)", $index, $key);
				$val = $humanid;
				
				//
				if (!nxs_tasks_isheadless())
	  		{		
		  		$keyvalues[$key] = $val;
		  	}
		  	else
		  	{
		  		$result["console"][] = "Storing $key $val";
		  		nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, $key, $val);
		  	}
				
				$index++;
			}
			
			if (!nxs_tasks_isheadless())
	  	{
	  		$index = 0;
	  		
	  		//$result["console"][] = json_encode($keyvalues);
	  		
	  		foreach ($keyvalues as $key => $val)
	  		{
	  			$postfix = "_{$index}";
	  			$sc_parameters .= "set_inputparameter_field{$postfix}='{$key}' function{$postfix}='staticvalue' value{$postfix}='{$val}' ";
					$index++;
				}
				

				$sc = "[nxs_p001_task_instruction type='set_bulk_parameters' {$sc_parameters}]";
				//$result["console"][] = $sc;
				$result["console"][] = do_shortcode($sc);
			}
		}
		else if ($how == "jsoninputparameter")
		{
			$jsoninputparameter = $then_that_item["jsoninputparameter"];
			$store_output_prefix = $then_that_item["store_output_prefix"];	// can be empty
			$store_output_fields_containing = $then_that_item["store_output_fields_containing"];
			$store_output_fields_containing_pieces = explode(";", $store_output_fields_containing);
			
			$jsonstring = $inputparameters[$jsoninputparameter];
			
			//$jsonstring = str_replace("\"\"}", "\"}", $jsonstring);
			//$jsonstring = str_replace("boundary=\"", "boundary=", $jsonstring);
			
			
			$jsonarray = json_decode($jsonstring, true);
						
			$flattened = nxs_array_flattenarray($jsonarray, "{$store_output_prefix}", "_");
			
			error_log("taskinstruction;explode");
			foreach ($flattened as $key => $val)
			{
				error_log("keyval; {$key};{$val}");
				
				
				$should_include = false;
				if ($store_output_fields_containing == "*")
				{
					$should_include = true;
				}
				else
				{
					$key_without_prefix = substr($key, strlen($store_output_prefix));
					
					foreach ($store_output_fields_containing_pieces as $store_output_fields_containing_piece)
					{
						if (nxs_stringcontains($key_without_prefix, $store_output_fields_containing_piece))
						{
							$should_include = true;
							break;
						}
					}
				}
				
				if ($should_include)
				{
					$result["console"][] = "storing output $key (value: $val)";
					// $instancemeta["inputparameters"][$key] = $val;
					nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, $key, $val);
				}
				else
				{
					// $result["console"][] = "<span style='color: #ddd;'>output $key will not be stored as it doesnt meet the criteria</span>";
				}
			}
			
			//nxs_tasks_updateinstance($taskid, $taskinstanceid, $instancemeta);
			//nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, "test", "123456");
		}
  }
  else
  {
  	$result["console"][] = "wont do anything because of state; $state";
  }
  
  $result["result"] = "OK";
  
  return $result;
}