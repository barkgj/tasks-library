<?php

function nxs_task_instance_do_input_parameter($then_that_item, $taskid, $taskinstanceid)
{
	$marker = $then_that_item["marker"];
	$inputtype = $then_that_item["inputtype"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	 
	$name = $then_that_item["name"];
	if ($name == "")
	{
		$msg = "error: no name attribute set for shortcode nxs_p001_task_requireparameter";
		$result["console"][] = $msg;
  	$result["result"] = "OK";
  	return $result;
	}

	$value = $inputparameters[$name];
	
	$currenturl = nxs_geturlcurrentpage();
	$returnurl = $currenturl . "#{$marker}";
	
	if ($inputtype == "")
	{
		$inputtype = "text";
	}
	
	//
	// render
	//
	if (false)
	{
		//
	}
	else if ($inputtype == "text")
	{
		if ($value == "")
		{
			$html .= "<form action='https://global.nexusthemes.com/' method='POST'>";
			
			$html .= "<input type='hidden' name='nxs' value='task-gui' />";
			$html .= "<input type='hidden' name='action' value='updateparameter' />";
			$html .= "<input type='hidden' name='page' value='taskinstancedetail' />";
			
			$html .= "<input type='hidden' name='taskid' value='{$taskid}' />";
			
			$html .= "<input type='hidden' name='taskinstanceid' value='{$taskinstanceid}' />";
			$html .= "<br />";
			$html .= "<label>{$name}</label><br />";
			$html .= "<input type='hidden' name='name' value='{$name}' />";
			
			$html .= "<input size='100' type='text' name='value' value='' />";
			
			$html .= "<input type='hidden' name='returnurl' value='{$returnurl}' />";
			$html .= "<br />";
			$html .= "<input type='submit' value='Save' style='background-color: #CCC;'>";
			$html .= "</form>";
		}
		else
		{
			$currenturl = nxs_geturlcurrentpage();
			
			$html .= "<div class='toggle' style='display: none; background-color: red;'>";
			$html .= "<form action='https://global.nexusthemes.com/' method='POST'>";
			//$html .= "<label>nxs</label>";
			$html .= "<input type='hidden' name='nxs' value='task-gui' />";
			$html .= "<input type='hidden' name='page' value='taskinstancedetail' />";
			//$html .= "<br />";
			//$html .= "<label>action</label>";
			$html .= "<input type='hidden' name='action' value='updateparameter' />";
			//$html .= "<br />";
			//$html .= "<label>taskid</label>";
			$html .= "<input type='hidden' name='taskid' value='{$taskid}' />";
			//$html .= "<br />";
			//$html .= "<label>taskinstanceid</label>";
			$html .= "<input type='hidden' name='taskinstanceid' value='{$taskinstanceid}' />";
			$html .= "<br />";
			$html .= "<label>{$name}</label>";
			$html .= "<input type='hidden' name='name' value='{$name}' />";
			//$html .= "<br />";
			//$html .= "<label>value</label>";
			
			$escapedvalue = nxs_render_html_escape_singlequote($value);
			$html .= "<input style='width: 95%;' type='text' name='value' value='{$escapedvalue}' />";
			
			$html .= "<input type='hidden' name='returnurl' value='{$returnurl}' />";
			$html .= "<br />";
			$html .= "<input type='submit' value='Save' style='background-color: #CCC;' />";
			$html .= "</form>";
			$html .= "<a href='#' onclick='jQuery(this).closest(\".INDENTED\").find(\".toggle\").toggle(); return false;'>cancel</a>";
			$html .= "</div>";
			
			$edittriggerhtml = " <a href='#' onclick='jQuery(this).closest(\".INDENTED\").find(\".toggle\").toggle(); return false;'><span style='display: inline-block; transform: rotateZ(90deg);'>&#9998;</span></a>";
			// copy to clipboard
			$copytoclipboardhtml = " <a href='#' onclick='navigator.clipboard.writeText(\"{$escapedvalue}\"); return false;'>copy</a>";
			$html .= "<div style='display: block;' class='toggle'><label>{$name}</label>:<span>{$value}</span>{$edittriggerhtml} {$copytoclipboardhtml}</div>";		
		}
		
		$result["console"][] = $html;
	}
	else if ($inputtype == "dropdown")
	{
		$value_exploded = explode(";", $value);
		
		$enable_multiple = $then_that_item["enable_multiple"];
		$items_string = $then_that_item["items"];	// example RECURS=recurs;ONE_OF_A_KIND=one of a kind
		
		if (false)
		{
			//
		}
		else if (nxs_stringstartswith($items_string, "datasource=ixplatform"))
		{
			// for example items='datasource=ixplatform;schema=SCHEMA;visualfield=FIELD1;datafield=FIELD2'
			$configuration = array();
			$configuration_key_values = explode(";", $items_string);
			foreach ($configuration_key_values as $configuration_key_value)
			{
				$key_value_pieces = explode("=", $configuration_key_value, 2);
				$key = $key_value_pieces[0];
				$val = $key_value_pieces[1];
				if (isset($configuration[$key])) { nxs_webmethod_return_nack("items=ixplatform; key $key occurs multiple times; value is therefore ambiguous. unable to proceed"); }
				$configuration[$key] = $val;
			}
			
			$schema = $configuration["schema"];
			if ($schema == "") { nxs_webmethod_return_nack("items=ixplatform; schema not set"); }
			
			$visualfields_string = $configuration["visualfields"];
			if ($visualfields_string == "")
			{
				$visualfield = $configuration["visualfield"];
				if ($visualfield == "") { nxs_webmethod_return_nack("items=ixplatform; visualfield not set"); }
				$visualfields_string = $visualfield;
			}
			
			$visualfields = explode("|", $visualfields_string);
			
				
			$datafield = $configuration["datafield"];
			if ($datafield == "") { nxs_webmethod_return_nack("items=ixplatform; datafield not set"); }
			
			global $nxs_g_modelmanager;
			$items = $nxs_g_modelmanager->gettaxonomypropertiesofallmodels(array("singularschema" => $schema));
			
			// todo; support filters
			
			$items_to_text = array();
			foreach ($items as $item)
			{
				if (!isset($item[$datafield])) { nxs_webmethod_return_nack("datafield is not a column in ixplatform?"); }
				
				$visual = "";
				foreach ($visualfields as $visualfield)
				{
					if (!isset($item[$visualfield])) { nxs_webmethod_return_nack("visualfield is not a column in ixplatform?"); }
					
					$visual .= $item[$visualfield] . " ";
				}
				$visual = trim($visual);
				
				$data = $item[$datafield];
				$items_to_text[$data] = $visual;
			}
		}
		else if (nxs_stringstartswith($items_string, "datasource=api"))
		{
			// example [nxs_p001_task_instruction type='input-parameter' name='invoice_id' inputtype='dropdown' items='datasource=api;api=list-purchase-invoices-for-year;api_filters=year={{invoice_year}};api_itemcontainer=invoices']
			$configuration = array();
			$configuration_key_values = explode(";", $items_string);
			foreach ($configuration_key_values as $configuration_key_value)
			{
				$key_value_pieces = explode("=", $configuration_key_value, 2);
				$key = $key_value_pieces[0];
				$val = $key_value_pieces[1];
				if (isset($configuration[$key])) { nxs_webmethod_return_nack("items=ixplatform; key $key occurs multiple times; value is therefore ambiguous. unable to proceed"); }
				$configuration[$key] = $val;
			}
			
			$service = $configuration["service"];
			if ($service == "") { nxs_webmethod_return_nack("items=api; service not set"); }
			
			// convert service to service_id
			if (true)
			{
		    $service_schema = "nxs.itil.configurationitems.api.service";
		    global $nxs_g_modelmanager;
		    $a = array
		    (
		      "singularschema" => $service_schema
		    );
		    $allservices = $nxs_g_modelmanager->gettaxonomypropertiesofallmodels($a);
		    foreach ($allservices as $entry) 
		    {
		      $entry_id       = $entry["nxs.itil.configurationitems.api.service_id"];
		      $currentservice = $entry["service"];
		      if ($service == $currentservice) {
		        // found
		        $service_id = $entry_id;
		      }
		    }
		    
		    if ($service_id == "") 
		    {
		      $result["result"] = "NACK";
		      $result["nack_details"] = array(
		        "message" => "shortcode error; {$type} service not found ({$service})"
		      );
		      return $result;
		    }
			}
			
			$service_atts_string = $configuration["service_atts"];
			// if ($service_atts == "") { nxs_webmethod_return_nack("items=api; service_atts not set"); }
			// for example "year={{invoice_year}}"
			// replace any placeholders in the service_atts
			$service_atts_string = nxs_filter_translatesingle($service_atts_string, "{{", "}}", $inputparameters);
			// for example "year:2018^month:4"
			
			$service_atts = array();
			$service_atts_explodedkeyvalues = explode("^", $service_atts_string);
			foreach ($service_atts_explodedkeyvalues as $service_atts_explodedkeyvalue)
			{
				$service_atts_explodedkey_and_value = explode(":", $service_atts_explodedkeyvalue);
				$service_atts_key = $service_atts_explodedkey_and_value[0];
				$service_atts_value = $service_atts_explodedkey_and_value[1];
				$service_atts[$service_atts_key] = $service_atts_value;
			}
			
			//$html .= "service_atts: " . json_encode($service_atts);
			
			// build up url
			
			global $nxs_g_modelmanager;
		  $a = array
		  (
		    "modeluri" => "{$service_id}@nxs.itil.configurationitems.api.service"
		  );
		  $service_properties = $nxs_g_modelmanager->getmodeltaxonomyproperties($a);
		  $api_id = $service_properties["nxs.itil.configurationitems.api_id"];
		  $a = array
		  (
		    "modeluri" => "{$api_id}@nxs.itil.configurationitems.api"
		  );
		  
		  $api_properties = $nxs_g_modelmanager->getmodeltaxonomyproperties($a);
		  
		  $api = $api_properties["api"];
		  $scheme = $api_properties["scheme"];
		  $hostname = $api_properties["hostname"];
		  
		  $service = $service_properties["service"];
		  
		  $invoke_api_url = "{$scheme}://{$hostname}/api/1/prod/{$service}/?nxs={$api}-api&nxs_json_output_format=prettyprint";
		  
		  // append service attributes
		  foreach ($service_atts as $key => $val)
		  {
		  	$invoke_api_url = nxs_addqueryparametertourl_v2($invoke_api_url, $key, $val, true, true);
		  }
		  
		  //$html .= "about to invoke: " . $invoke_api_url;
		  
		  $url_args = array();
		  $url_args["url"] = $invoke_api_url;
		  
		  $invoke_api_string = nxs_geturlcontents($url_args);
		  $invoke_api_result = json_decode($invoke_api_string, true);
		  
		  if ($invoke_api_result["result"] != "OK") 
		  {
		  	// TODO: write property to inputparameters of this instance?
		  	
		  	$message = $invoke_api_result["message"];
		    $result["result"] = "NACK";
		    $result["nack_details_v2"] = array();
		    $result["nack_details"] = array
		    (
		      "invoke_api_result" => $invoke_api_result
		    );
		    return $result;
		  }
		  
		  $service_itemcontainer = $configuration["service_itemcontainer"];
		  
		  //$html .= "<br />json api result:<br />" . json_encode($invoke_api_result);
		  
		  $items = $invoke_api_result[$service_itemcontainer];
		  
		  foreach ($items as $item)
		  {
		  	$items_to_text[$item] = $item;
		  }
		}
		else
		{
			$items_keyvalue = explode(";", $items_string);
			foreach ($items_keyvalue as $keyvalue)
			{
				if (nxs_stringcontains($keyvalue, "="))
				{
					// key=value
					$pieces = explode("=", $keyvalue);
					$k = $pieces[0];
					$v = $pieces[1];
					$items_to_text[$k] = $v;
				}
				else
				{
					$items_to_text[$keyvalue] = $keyvalue;
				}
			}
		}
		
		$html .= "<form action='https://global.nexusthemes.com/' method='POST'>";
		$html .= "<input type='hidden' name='nxs' value='task-gui' />";
		$html .= "<input type='hidden' name='page' value='taskinstancedetail' />";
		$html .= "<input type='hidden' name='action' value='updateparameter' />";
		$html .= "<input type='hidden' name='taskid' value='{$taskid}' />";
		$html .= "<input type='hidden' name='taskinstanceid' value='{$taskinstanceid}' />";
		$html .= "<br />";
		$html .= "<label>{$name}</label>";
		
		if ($enable_multiple == "true")
		{
			$html .= "<br />";
		}
		
		$html .= "<input type='hidden' name='name' value='{$name}' />";
		
		$multiple_att = "";
		
		if ($enable_multiple == "true")
		{
			$multiple_att = "multiple size='" . (count($items_keyvalue) + 1) . "'";
			$name_att = "value[]";	
		}
		else
		{
			$name_att = "value";
		}
		
		$html .= "<select name='{$name_att}' {$multiple_att}>";
		if (!array_key_exists($value, $items_to_text))
		{
			if ($value == "")
			{
				$html .= "<option value='{$value}'></option>";
			}
		}
		foreach ($items_to_text as $val => $text)
		{
			$selected_att = "";
			if ($val == $value || in_array($val, $value_exploded))
			{
				$selected_att = "selected";
			}
			$html .= "<option value='{$val}' {$selected_att}>{$text}</option>";
		}
		$html .= "</select>";
		
		if ($enable_multiple == "true")
		{
			$html .= "<br />";
		}
		
		$html .= "<input type='hidden' name='returnurl' value='{$returnurl}' />";
		$html .= "&nbsp;";
		$html .= "<input type='submit' value='Save' style='background-color: #CCC;' />";
		$html .= "</form>";
		
		$edittriggerhtml = "";
		
		// copy to clipboard
		$escapedvalue = nxs_render_html_escape_singlequote($value);
		$copytoclipboardhtml = " <a href='#' onclick='navigator.clipboard.writeText(\"{$escapedvalue}\"); return false;'>copy</a>";
		$html .= "* <div style='display: inline-block;' class='toggle'><label>{$name}</label>:<span>{$value}</span>{$edittriggerhtml} {$copytoclipboardhtml}</div>";		
		
		$result["console"][] = $html;
	}
	else 
	{
		$result["console"][] = "Unsupported inputtype; $inputtype";
	}
	
	$result["result"] = "OK";
	return $result;
}