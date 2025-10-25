<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Booking;
use App\Entity\BookingTimeline;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\CompletedEvent;

final class BookingWorkflowTimelineSubscriber
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[AsEventListener(event: 'workflow.booking.completed')]
    public function onCompleted(CompletedEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof Booking) {
            return;
        }

        // Ne rien faire si le Booking n'est pas encore géré par Doctrine
        if (!$this->em->contains($subject)) {
            return;
        }

        $transition = $event->getTransition();
        $from = $transition->getFroms()[0] ?? null;
        $to = $transition->getTos()[0] ?? $subject->getStatusMarking();
        if (null === $to) {
            return;
        }

        // ✅ Conformité tests actuels : actor NULL (sera géré dans une micro-étape dédiée)
        $timeline = new BookingTimeline(
            booking: $subject,
            toStatus: $to,
            fromStatus: $from,
            actor: null,
            context: null,
            occurredAt: null,
        );

        $this->em->persist($timeline);
        $this->em->flush();
    }
}
