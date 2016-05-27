<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\UserBundle\Entity\Email;

class AttendeeRelationManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttendeeRelationManager */
    protected $attendeeRelationManager;

    /** @var User[] */
    protected $users;

    public function setUp()
    {
        $this->users = [
            'u1@example.com' => (new User())->setEmail('u1@example.com'),
            'u2@example.com' => (new User())->addEmail((new Email())->setEmail('u2@example.com')),
            'u3@example.com' => (new User())->setEmail('u3@example.com'),
            'u4@example.com' => (new User())->setEmail('u4@example.com'),
        ];

        $userRepository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->expects($this->any())
            ->method('findUsersByEmails')
            ->will($this->returnCallback(function (array $emails) {
                return array_values(array_intersect_key($this->users, array_flip($emails)));
            }));

        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry
            ->expects($this->any())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->will($this->returnValue($userRepository));

        $nameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $nameFormatter->expects($this->any())
            ->method('format')
            ->will($this->returnCallback(function ($person) {
                return $person->getFullName();
            }));

        $this->attendeeRelationManager = new AttendeeRelationManager($registry, $nameFormatter);
    }

    public function testBindAttendees()
    {
        $attendees = $this->getInitialAttendees();
        $this->attendeeRelationManager->bindAttendees($attendees);

        $this->assertEquals($this->getExpectedAttendees(), $attendees);
    }

    protected function getInitialAttendees()
    {
        return [
            (new Attendee(1))
                ->setEmail('u1@example.com'),
            (new Attendee())
                ->setEmail('u2@example.com'),
            (new Attendee())
                ->setEmail('u3@example.com'),
            (new Attendee())
                ->setEmail('nonExisting@example.com'),
            (new Attendee())
                ->setEmail('u4@example.com')
                ->setUser(new User()),
        ];
    }

    protected function getExpectedAttendees()
    {
        return [
            (new Attendee(1))
                ->setEmail('u1@example.com')
                 ->setUser($this->users['u1@example.com'])
                 ->setDisplayName($this->users['u1@example.com']->getFullName()),
            (new Attendee())
                ->setEmail('u2@example.com')
                ->setUser($this->users['u2@example.com'])
                ->setDisplayName($this->users['u2@example.com']->getFullName()),
            (new Attendee())
                ->setEmail('u3@example.com')
                ->setUser($this->users['u3@example.com'])
                ->setDisplayName($this->users['u3@example.com']->getFullName()),
            (new Attendee())
                ->setEmail('nonExisting@example.com'),
            (new Attendee())
                ->setEmail('u4@example.com')
                ->setUser(new User()),
        ];
    }
}
