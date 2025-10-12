<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

class AiServiceFormatter extends LineFormatter
{
    public function __construct()
    {
        parent::__construct(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
    }

    public function format(LogRecord $record): string
    {
        $output = parent::format($record);

        // Make the output more readable by formatting context
        if (!empty($record->context)) {
            $output = str_replace(
                json_encode($record->context),
                "\n" . json_encode($record->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                $output
            );
        }

        return $output . str_repeat('-', 80) . "\n";
    }
}
