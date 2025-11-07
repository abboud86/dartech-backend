<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\WorkflowEvents;

/**
 * Guards métier pour le workflow Booking.
 *
 * Empêche les transitions incohérentes :
 * - Aucune transition après CANCELLED
 * - Pas de retour en arrière
 * - Vérifie la validité de scheduled_at et estimated_amount
 */
final class BookingWorkflowGuardSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            WorkflowEvents::GUARD => ['onGuard', 0],
        ];
    }

    public function onGuard(GuardEvent $event): void
    {
        $booking = $event->getSubject();
        $transition = $event->getTransition()->getName();

        // 🔒 1) Blocage global après annulation
        if ('CANCELLED' === $booking->getStatus()) {
            $event->setBlocked(true);
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Cannot modify a cancelled booking.');
        }

        // 🔒 2) Blocage des transitions “retour en arrière”
        $reverseTransitions = [
            'confirm' => ['INQUIRY', 'PENDING'],
            'mark_pending' => ['INQUIRY'],
            'revert_inquiry' => ['PENDING', 'CONFIRMED'],
        ];

        if (isset($reverseTransitions[$transition])) {
            $forbiddenFrom = $reverseTransitions[$transition];
            if (in_array($booking->getStatus(), $forbiddenFrom, true)) {
                $event->setBlocked(true);
                throw new HttpException(Response::HTTP_FORBIDDEN, sprintf('Cannot perform transition "%s" from status "%s".', $transition, $booking->getStatus()));
            }
        }

        // 🔒 3) Validation métier sur scheduled_at (pas dans le passé)
        $scheduledAt = $booking->getScheduledAt();
        if ($scheduledAt instanceof \DateTimeImmutable && $scheduledAt < new \DateTimeImmutable('now')) {
            $event->setBlocked(true);
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Scheduled date cannot be in the past.');
        }

        // 🔒 4) Validation sur montant
        $amount = $booking->getEstimatedAmount();
        if (null !== $amount && ($amount < 1000 || $amount > 1000000)) {
            $event->setBlocked(true);
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Estimated amount out of allowed range.');
        }
    }
}
