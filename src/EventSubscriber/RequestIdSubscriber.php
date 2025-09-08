<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Observability\RequestIdProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final class RequestIdSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestIdProvider $provider,
        private readonly string $headerName = 'X-Request-Id',
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onKernelRequest', 1024], // très tôt
            ResponseEvent::class => ['onKernelResponse', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $id = $this->provider->ensureFor($event->getRequest());

        // Tag Sentry si présent (pas de dépendance dure au bundle)
        if (\function_exists('\Sentry\configureScope')) {
            \Sentry\configureScope(static function ($scope) use ($id): void {
                /** @var \Sentry\State\Scope $scope */
                $scope->setTag('request_id', $id);
            });
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $id = $this->provider->current();
        if (null !== $id) {
            $event->getResponse()->headers->set($this->headerName, $id);
        }
    }
}
