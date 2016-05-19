<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Oro\Bundle\CalendarBundle\Form\DataTransformer\UsersToAttendeesTransformer;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class AttendeeManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttendeeManager */
    protected $attendeeManager;

    /** @var ConverterInterface */
    protected $usersConverter;

    /** @var UsersToAttendeesTransformer */
    protected $usersToAttendeesTransformer;

    /** @var SecurityFacade */
    protected $securityFacade;

    public function setUp()
    {
        $this->usersConverter = $this->getMock('Oro\Bundle\FormBundle\Autocomplete\ConverterInterface');

        $this->usersToAttendeesTransformer = $this
            ->getMockBuilder('Oro\Bundle\CalendarBundle\Form\DataTransformer\UsersToAttendeesTransformer')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $this->attendeeManager = new AttendeeManager(
            $this->usersConverter,
            $this->usersToAttendeesTransformer,
            $this->securityFacade
        );
    }

    /**
     * @dataProvider attendeesToAutocompleteDataProvider
     *
     * @param Attendee $attendee
     * @param array    $result
     */
    public function testAttendeesToAutocompleteData($attendee, $result)
    {
        $this->usersConverter->expects($this->any())
            ->method('convertItem')
            ->will($this->returnCallback(function (User $user) {
                return [
                    'fullName' => $user->getEmail(), 'email' => $user->getEmail(),
                ];
            }));

        $autocompleteData = $this->attendeeManager->attendeesToAutocompleteData([$attendee]);

        $this->assertEquals(
            [$result],
            $autocompleteData
        );
    }

    /**
     * @return array
     */
    public function attendeesToAutocompleteDataProvider()
    {
        $user1 = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods(['getEmail'])
            ->getMock();

        $user2 = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods(['getEmail'])
            ->getMock();

        $calendarEvent1 = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\CalendarEvent')
            ->disableOriginalConstructor()
            ->setMethods(['getRelatedAttendee'])
            ->getMock();

        $calendarEvent2 = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\CalendarEvent')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $attendee1 = new Attendee(1);
        $attendee1->setEmail('name@example.com');
        $attendee1->setUser($user1);
        $attendee1->setCalendarEvent($calendarEvent1);

        $calendarEvent1->expects($this->any())
            ->method('getRelatedAttendee')
            ->will($this->returnValue($attendee1));

        $user1->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue('name@example.com'));

        $attendee2 = new Attendee(2);
        $attendee2->setEmail('user@example.com');
        $attendee2->setCalendarEvent($calendarEvent2);

        $attendee3 = new Attendee(3);
        $attendee3->setEmail('unlocked@example.com');
        $attendee3->setCalendarEvent($calendarEvent1);
        $attendee3->setUser($user2);

        $user2->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue('unlocked@example.com'));

        return [
            'have user and event owner' =>[
                'attendee' => $attendee1,
                'result' => ['fullName' => 'name@example.com', 'email' => 'name@example.com']
            ],
            'does not have user' =>[
                'attendee' => $attendee2,
                'result' => ['fullName' => 'user@example.com', 'email' => 'user@example.com', 'locked' => true]
            ],
            'have user and child event' =>[
                'attendee' => $attendee3,
                'result' => ['fullName' => 'unlocked@example.com', 'email' => 'unlocked@example.com']
            ],
        ];
    }
}
