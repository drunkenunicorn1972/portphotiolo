<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class EmailLogger
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $emailLogger)
    {
        $this->logger = $emailLogger;
    }

    public function logEmailSent(
        string $to,
        string $subject,
        string $context = '',
        array $additionalData = []
    ): void {
        $this->logger->info('Email sent successfully', [
            'to' => $to,
            'subject' => $subject,
            'context' => $context,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            ...$additionalData
        ]);
    }

    public function logEmailFailed(
        string $to,
        string $subject,
        string $errorMessage,
        string $context = '',
        array $additionalData = []
    ): void {
        $this->logger->error('Email sending failed', [
            'to' => $to,
            'subject' => $subject,
            'error' => $errorMessage,
            'context' => $context,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            ...$additionalData
        ]);
    }

    public function logEmailQueued(
        string $to,
        string $subject,
        string $context = '',
        array $additionalData = []
    ): void {
        $this->logger->info('Email queued for sending', [
            'to' => $to,
            'subject' => $subject,
            'context' => $context,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            ...$additionalData
        ]);
    }
}
