<?php

namespace barkgj\tasks\taskinstruction;

use barkgj\functions;
use barkgj\tasks\tasks;
use barkgj\tasks\itaskinstruction;

class set_stateparameter implements itaskinstruction
{
	public function __construct()
	{
		//
	}

	function execute($taskid, $taskinstanceid, $attributes)
	{
		$result["console"][] = "SET INPUT PARAMETER TO VALUE";
		
		$key = $attributes["key"];
		$value = $attributes["value"];
		
		tasks::appendstateparameter_for_taskinstance($taskid, $taskinstanceid, $key, $value);

		$result["console"][] = "SET VALUE OF $key TO $value";
		$result["result"] = "OK";
		
		return $result;
	}
}