<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\CalendarBundle\Form\EventListener\AttendeesSubscriber;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;

class AttendeesSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttendeeRelationManager */
    protected $attendeeRelationManager;

    /** @var AttendeesSubscriber */
    protected $attendeesSubscriber;

    public function setUp()
    {
        $this->attendeeRelationManager = $this
            ->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attendeesSubscriber = new AttendeesSubscriber($this->attendeeRelationManager);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SUBMIT  => ['fixSubmittedData', 100],
                FormEvents::POST_SUBMIT => ['postSubmit', -100],
            ],
            $this->attendeesSubscriber->getSubscribedEvents()
        );
    }

    /**
     * @dataProvider preSubmitProvider
     */
    public function testPreSubmit($eventData, $formData, $expectedData)
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($formData));

        $event = new FormEvent($form, $eventData);
        $this->attendeesSubscriber->fixSubmittedData($event);
        $this->assertEquals($expectedData, $event->getData());
    }

    public function preSubmitProvider()
    {
        return [
            'empty attendees' => [
                [
                    'displayName' => 'existing',
                    'email' => 'existing@example.com',
                ],
                [],
                [
                    'displayName' => 'existing',
                    'email' => 'existing@example.com',
                ],
            ],
            'empty data' => [
                [],
                [
                    'displayName' => 'existing',
                    'email' => 'existing@example.com',
                ],
                [],
            ],
            'missing email and displayName in attendees' => [
                [
                    [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayname' => 'new',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayname' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
                new ArrayCollection([
                    (new Attendee())
                        ->setDisplayName('existing')
                        ->setEmail('existing@example.com'),
                    (new Attendee()),
                ]),
                [
                    [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayname' => 'new',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayname' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
            ],
            'missing email in data' => [
                [
                    [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayname' => 'new',
                    ],
                    [
                        'displayname' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
                new ArrayCollection([
                    (new Attendee())
                        ->setDisplayName('existing')
                        ->setEmail('existing@example.com'),
                    (new Attendee())
                        ->setDisplayName('toBeRemoved')
                        ->setEmail('toBeRemoved@example.com'),
                ]),
                [
                    [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayname' => 'new',
                    ],
                    [
                        'displayname' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
            ],
            'valid data' => [
                [
                    [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayname' => 'new',
                        'email' => 'new@example.com',
                    ],
                    [
                        'displayname' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
                new ArrayCollection([
                    (new Attendee())
                        ->setDisplayName('existing')
                        ->setEmail('existing@example.com'),
                    (new Attendee())
                        ->setDisplayName('toBeRemoved')
                        ->setEmail('toBeRemoved@example.com'),
                ]),
                [
                    0 => [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    2 => [
                        'displayname' => 'new',
                        'email' => 'new@example.com',
                    ],
                    3 => [
                        'displayname' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
            ],
        ];
    }

    public function testPostSubmit()
    {
        $attendees = new ArrayCollection([new Attendee(1)]);

        $event = new FormEvent(
            $this->getMock('Symfony\Component\Form\FormInterface'),
            $attendees
        );

        $this->attendeeRelationManager->expects($this->once())
            ->method('bindAttendees')
            ->with($attendees);

        $this->attendeesSubscriber->postSubmit($event);
    }

    public function testPostSubmitWithNullData()
    {
        $this->attendeeRelationManager->expects($this->never())
            ->method('bindAttendees');

        $this->attendeesSubscriber->postSubmit(
            new FormEvent($this->getMock('Symfony\Component\Form\FormInterface'), null)
        );
    }
}
