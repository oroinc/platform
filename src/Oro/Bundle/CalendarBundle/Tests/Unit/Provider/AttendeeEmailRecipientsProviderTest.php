<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;
use Oro\Bundle\CalendarBundle\Provider\AttendeeEmailRecipientsProvider;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;

class AttendeeEmailRecipientsProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttendeeEmailRecipientsProvider */
    protected $provider;

    /** @var AttendeeRepository */
    protected $attendeeRepository;

    /** @var EmailRecipientsHelper */
    protected $emailRecipientsHelper;

    public function setUp()
    {
        $this->attendeeRepository = $this
            ->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->any())
            ->method('getRepository')
            ->with('Oro\Bundle\CalendarBundle\Entity\Attendee')
            ->will($this->returnValue($this->attendeeRepository));

        $this->emailRecipientsHelper = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new AttendeeEmailRecipientsProvider(
            $registry,
            $this->emailRecipientsHelper
        );
    }

    public function testGetSection()
    {
        $this->assertEquals('oro.calendar.autocomplete.attendees', $this->provider->getSection());
    }

    public function testGetRecipients()
    {
        $args = new EmailRecipientsProviderArgs(null, 'query', 100);

        $this->attendeeRepository->expects($this->once())
            ->method('getEmailRecipients')
            ->with(null, 'query', 100)
            ->will($this->returnValue([]));

        $this->emailRecipientsHelper->expects($this->once())
            ->method('plainRecipientsFromResult')
            ->with([])
            ->will($this->returnValue([]));

        $this->assertEquals([], $this->provider->getRecipients($args));
    }
}
