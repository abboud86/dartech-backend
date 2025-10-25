<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\Booking;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

final class BookingFlowTest extends KernelTestCase
{
    private WorkflowInterface $workflow;

    protected function setUp(): void
    {
        self::bootKernel();
        /** @var WorkflowInterface $wf */
        $wf = self::getContainer()->get('state_machine.booking');
        $this->workflow = $wf;
    }

    public function testInquiryToDoneHappyPath(): void
    {
        $booking = new Booking();
        $booking->setStatusMarking('INQUIRY');

        $this->assertTrue($this->workflow->can($booking, 'to_contacted'));
        $this->workflow->apply($booking, 'to_contacted');
        $this->assertSame('CONTACTED', $booking->getStatusMarking());

        $this->assertTrue($this->workflow->can($booking, 'to_scheduled'));
        $this->workflow->apply($booking, 'to_scheduled');
        $this->assertSame('SCHEDULED', $booking->getStatusMarking());

        $this->assertTrue($this->workflow->can($booking, 'to_done'));
        $this->workflow->apply($booking, 'to_done');
        $this->assertSame('DONE', $booking->getStatusMarking());
    }

    public function testCancelFromInquiry(): void
    {
        $booking = new Booking();
        $booking->setStatusMarking('INQUIRY');

        $this->assertTrue($this->workflow->can($booking, 'to_canceled'));
        $this->workflow->apply($booking, 'to_canceled');
        $this->assertSame('CANCELED', $booking->getStatusMarking());
    }
}
