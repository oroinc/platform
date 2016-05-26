<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CalendarBundle\Form\DataTransformer\UsersToAttendeesTransformer;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Attendee as AttendeeEntity;

class UsersToAttendeesTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var UsersToAttendeesTransformer */
    protected $usersToAttendeesTransformer;

    /** @var User[] */
    protected $usersById = [];

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->usersById = [
            1 => (new User(1))
                ->setFirstName('First')
                ->setEmail('user@example.com')
        ];
    }

    public function setUp()
    {
        $usersToIdsTransformer = $this->getMock('Symfony\Component\Form\DataTransformerInterface');
        $usersToIdsTransformer->expects($this->any())
            ->method('transform')
            ->will($this->returnCallback(function (array $users) {
                return array_map(
                    function (User $user) {
                        return $user->getId();
                    },
                    $users
                );
            }));
        $usersToIdsTransformer->expects($this->any())
            ->method('reverseTransform')
            ->will($this->returnCallback(function (array $ids) {
                return array_values(array_intersect_key($this->usersById, array_flip($ids)));
            }));
        $this->usersToAttendeesTransformer = new UsersToAttendeesTransformer($usersToIdsTransformer);
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform($value, $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->usersToAttendeesTransformer->transform($value));
    }

    public function transformDataProvider()
    {
        $attendee1 = new Attendee();
        $attendee1->setEmail('attendee1@example.com');
        $attendee1->setUser(
            (new User(1))
                ->setEmail('user@example.com')
        );

        $attendee2 = new Attendee();
        $attendee2->setEmail('attendee2@example.com');

        return [
            [null, null],
            [
                [
                    $attendee1,
                    $attendee2,
                ],
                [
                    1,
                    json_encode(['value' => 'attendee2@example.com']),
                ]
            ],
        ];
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($value, $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->usersToAttendeesTransformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider()
    {
        $attendee1 = (new AttendeeEntity())
            ->setDisplayName('First ')
            ->setEmail('user@example.com')
            ->setUser($this->usersById[1]);

        $attendee2 = (new AttendeeEntity())
            ->setDisplayName('attendee2@example.com')
            ->setEmail('attendee2@example.com');

        return [
            [null, []],
            ['', []],
            [
                [
                    1,
                    json_encode(['value' => 'attendee2@example.com']),
                ],
                new ArrayCollection([
                    $attendee1,
                    $attendee2,
                ]),
            ],
        ];
    }
}
