<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class NoStoreHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Only master (main) request
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // We only care about /v1/me, and only for JSON responses.
        $path = $request->getPathInfo();
        if ('/v1/me' !== $path) {
            return;
        }
        if (!$response instanceof JsonResponse) {
            return;
        }

        // Add RFC7234 no-store guards (idempotent: will just overwrite if present)
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('Pragma', 'no-cache');
        // Content-Type is already set by JsonResponse, leave it as-is.
    }
}
