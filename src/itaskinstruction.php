<?php

namespace barkgj\tasks;

interface itaskinstruction
{
    public function execute($taskid, $taskinstanceid, $attributes);
}