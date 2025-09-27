<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

final class SentryTestController extends AbstractController
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    #[Route('/_sentry-test', name: 'sentry_test')]
    public function testLog(): never
    {
        // 1) Un log ERROR doit partir vers Sentry via Monolog handler "sentry"
        $this->logger->error('My custom logged error.', ['some' => 'Context Data']);

        // 2) Une exception non-catchée doit être capturée aussi
        throw new \RuntimeException('Example exception.');
    }
}
