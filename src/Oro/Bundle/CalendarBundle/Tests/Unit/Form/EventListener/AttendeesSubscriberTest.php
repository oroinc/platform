<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormEvent;

use Oro\Bundle\CalendarBundle\Form\EventListener\AttendeesSubscriber;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\UserBundle\Entity\Email;

class AttendeesSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttendeesSubscriber */
    protected $attendeesSubscriber;

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

        $this->attendeesSubscriber = new AttendeesSubscriber($registry);
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
            'missing email' => [
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
        $event = new FormEvent(
            $this->getMock('Symfony\Component\Form\FormInterface'),
            new ArrayCollection($this->getInitialAttendees())
        );
        $this->attendeesSubscriber->postSubmit($event);

        $this->assertEquals(
            new ArrayCollection($this->getExpectedAttendees()),
            $event->getData()
        );
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
                 ->setUser($this->users['u1@example.com']),
            (new Attendee())
                ->setEmail('u2@example.com')
                ->setUser($this->users['u2@example.com']),
            (new Attendee())
                ->setEmail('u3@example.com')
                ->setUser($this->users['u3@example.com']),
            (new Attendee())
                ->setEmail('nonExisting@example.com'),
            (new Attendee())
                ->setEmail('u4@example.com')
                ->setUser(new User()),
        ];
    }
}
