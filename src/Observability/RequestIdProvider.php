<?php

declare(strict_types=1);

namespace App\Observability;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

final class RequestIdProvider
{
    public const ATTR = '_request_id';

    /**
     * @param list<string> $headerSynonyms
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly string $headerName = 'X-Request-Id',
        /** @var list<string> */
        private readonly array $headerSynonyms = ['X-Correlation-Id'],
        private readonly bool $trustClientId = true,
    ) {
    }

    /**
     * Ensure the request has a request_id:
     * - reuse a client-provided id if allowed and valid
     * - otherwise generate a UUIDv4
     * - store it on Request attributes
     */
    public function ensureFor(Request $request): string
    {
        if ($request->attributes->has(self::ATTR)) {
            return (string) $request->attributes->get(self::ATTR);
        }

        $id = null;

        if ($this->trustClientId) {
            $candidates = array_merge([$this->headerName], $this->headerSynonyms);
            foreach ($candidates as $h) {
                $raw = $request->headers->get($h);
                if ($raw && ($id = $this->sanitize($raw))) {
                    break;
                }
            }
        }

        if (!$id) {
            $id = Uuid::v4()->toRfc4122();
        }

        $request->attributes->set(self::ATTR, $id);

        return $id;
    }

    /** Returns the current request_id if any (null outside HTTP context). */
    public function current(): ?string
    {
        return $this->requestStack->getCurrentRequest()?->attributes->get(self::ATTR);
    }

    /**
     * Very permissive but safe: trim, length cap, allowed charset.
     * Accepts UUID/ULID/trace-like tokens (A-Za-z0-9_.-), up to 128 chars.
     */
    private function sanitize(string $raw): ?string
    {
        $raw = trim($raw);
        if ('' === $raw || strlen($raw) > 128) {
            return null;
        }
        if (!preg_match('/^[A-Za-z0-9_.-]+$/', $raw)) {
            return null;
        }

        return $raw;
    }
}
