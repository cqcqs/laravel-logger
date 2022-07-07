<?php

namespace Cqcqs\Logger\Components;

class JsonFormatter extends \Monolog\Formatter\JsonFormatter
{
    public function format(array $record): string
    {
        $record['trace_id'] = TRACE_ID;
        return parent::format($record);
    }
}
