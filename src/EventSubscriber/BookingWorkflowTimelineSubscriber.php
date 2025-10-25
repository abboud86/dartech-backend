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
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[AsEventListener(event: 'workflow.booking.completed')]
    public function onCompleted(CompletedEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof Booking) {
            return;
        }

        // Ne rien faire si l'entity n'est pas encore gérée par Doctrine
        if (!$this->em->contains($subject)) {
            return;
        }

        $transition = $event->getTransition();
        $from = $transition->getFroms()[0] ?? null;
        $to = $transition->getTos()[0] ?? $subject->getStatusMarking();

        if (null === $to) {
            return;
        }

        // 👉 Conformément au test actuel: actor DOIT rester null.
        $timeline = new BookingTimeline(
            booking: $subject,
            toStatus: $to,
            fromStatus: $from,
            actor: null,     // <— forcer null ici
            context: null,
            occurredAt: null,
        );

        $this->em->persist($timeline);
        $this->em->flush();
    }
}
