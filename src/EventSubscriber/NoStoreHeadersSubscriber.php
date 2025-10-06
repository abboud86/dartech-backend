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
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Only JSON responses
        if (!$response instanceof JsonResponse) {
            return;
        }

        $path = $request->getPathInfo();

        // Guard only /v1/me and every /v1/auth/* endpoint
        $isMe = ('/v1/me' === $path);
        $isAuth = str_starts_with($path, '/v1/auth/');

        if (!$isMe && !$isAuth) {
            return;
        }

        // RFC7234/RFC9111: ensure sensitive payloads are not stored
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('Pragma', 'no-cache');
    }
}
