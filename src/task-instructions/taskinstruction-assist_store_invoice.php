<?php

require_once("/srv/generic/libraries-available/nxs-finance/nxs-finance.php");

function nxs_task_instance_do_assist_store_invoice($then_that_item, $taskid, $taskinstanceid)
{
	// $marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$state = $instancemeta["state"];
	
  $result = array();
  
  if ($state == "STARTED")
  {
  	$attachments_json = $inputparameters["attachments_json"];
  	if ($attachments_json == "")
  	{
  		// backwards compatibility
  		$attachments_json = $inputparameters["api_inputparameterstoappend_attachments_json"];
  	}
  	$items = json_decode($attachments_json, true);
		$vendor_id = $then_that_item["vendor_id"];
		$invoicedate_yyyy = $then_that_item["invoicedate_yyyy"];
		$invoicedate_mm = $then_that_item["invoicedate_mm"];

		$errors = array();
		if (count($items) == 0)
		{
			$errors[] = "0 attachments found, 1 required, consider to refetch the attachments using the instruction on the next line";
			$errors[] = do_shortcode("[nxs_p001_task_instruction type='invoke_api' service='interpret-conversation' helpscoutnumber='{{original_helpscoutticketnr}}' store_output='true' store_output_prefix='api_' store_output_fields_containing='attachments_json']");
		}
		if (count($items) > 1)
		{
			$invoice_attachment_index = $inputparameters["invoice_attachment_index"];
			if ($invoice_attachment_index == "")
			{
				$errors[] = "multiple attachments found, select the invoice_attachment_index first";	
			}
			
			// compile a dropdownlist based on all available attachments
			$ddl_index = -1;
			$ddl_items = array();
			foreach ($items as $item)
			{
				$ddl_index++;
				$filename = $item["fileName"];
				$ddl_items[] = "{$ddl_index}={$filename}";
			}
			$ddl_imploded = implode(";", $ddl_items);
			$result["console"][] = do_shortcode("[nxs_p001_task_instruction type='input-parameter' name='invoice_attachment_index' inputtype='dropdown' items='{$ddl_imploded}']");
		}
		if ($vendor_id == "" || nxs_stringcontains($vendor_id, '{'))
		{
			$errors[] = "vendor_id not set";
		}
		if ($invoicedate_yyyy == "" || nxs_stringcontains($invoicedate_yyyy, '{'))
		{
			$errors[] = "invoicedate_yyyy not set";
		}
		if ($invoicedate_mm == "" || nxs_stringcontains($invoicedate_mm, '{'))
		{
			$errors[] = "invoicedate_mm not set";
		}
		
		$canproceed = (count($errors) == 0);
		
  	if ($canproceed)
  	{
  		$invoice_attachment_index = $inputparameters["invoice_attachment_index"];
  		if ($invoice_attachment_index == "")
  		{
  			$invoice_attachment_index = 0;
  		}
  		$item = $items[$invoice_attachment_index];

			$filename = $item["fileName"];
			$mimetype = $item["mimeType"];
			$size = $item["size"];
			$downloadurl = $item["webUrl"];
			
			$filenamepieces = explode(".", $filename);
			
			$invoiceid = $filenamepieces[0];
			
			// TODO: use nxs_finance_getvendorinvoicecontainingfolder($year)
			$path = "/srv/mnt/finance/finance Nexus/{$invoicedate_yyyy}/Facturen_inkoop_kosten/invoice_v2_vendorid_{$vendor_id}_{$invoicedate_yyyy}_{$invoicedate_mm}_{$filename}";
			
			$result["console"][] = "invoiceid: $invoiceid";
			$result["console"][] = "path: $path";
			
			if (file_exists($path))
			{
				$bytes = filesize($path);
				$result["console"][] = "invoice already there ({$bytes} bytes)";
			}
			else
			{
				$result["console"][] = "storing invoice ... ";
				$data = file_get_contents($downloadurl);
				
				// ensure container folder exists
				require_once("/srv/generic/libraries-available/nxs-fs/nxs-fs.php");
				nxs_fs_createcontainingfolderforfilepathifnotexists($path);
				
				$r = file_put_contents($path, $data);
				if ($r === false)
				{
					$msg = "error downloading invoice";
					error_log($msg);
					nxs_webmethod_return_nack($msg);
				}
				$size = filesize($path);
				
				$result["console"][] = "finished writing $size bytes";
			}
		}
		else
		{
			foreach ($errors as $error)
			{
				$result["console"][] = $error;
			}
		}
  }
    
  $result["result"] = "OK";
  
  return $result;
}