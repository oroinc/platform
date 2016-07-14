<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Autocomplete;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Oro\Bundle\CalendarBundle\Autocomplete\AttendeeSearchHandler;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;

class AttendeeSearchHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|Indexer */
    protected $indexer;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager */
    protected $om;

    /** @var EntityRepository */
    protected $entityRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AttendeeRelationManager */
    protected $attendeeRelationManager;

    /** @var AttendeeSearchHandler */
    protected $attendeeSearchHandler;

    public function setUp()
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())
            ->method('trans')
            ->will(
                $this->returnCallback(
                    function ($id) {
                        return $id;
                    }
                )
            );

        $this->indexer = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\Indexer')
            ->disableOriginalConstructor()
            ->getMock();

        $activityManager = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Manager\ActivityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $entityClassNameHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $mapper = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\ObjectMapper')
            ->disableOriginalConstructor()
            ->getMock();

        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->attendeeRelationManager = $this
            ->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(['findById'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->om->expects($this->any())
            ->method('getRepository')
            ->with('entity')
            ->will($this->returnValue($this->entityRepository));

        $this->attendeeSearchHandler = new AttendeeSearchHandler(
            new TokenStorage(),
            $translator,
            $this->indexer,
            $activityManager,
            $configManager,
            $entityClassNameHelper,
            $this->om,
            $mapper,
            $dispatcher,
            $this->attendeeRelationManager
        );
    }

    public function testSearch()
    {
        $items = [
            new Item($this->om, 'entity', 1, 'record1'),
            new Item($this->om, 'entity', 2, 'record2'),
        ];

        $users = [
            (new User(1))
                ->setEmail('user1@example.com')
                ->setFirstName('user1'),
            (new User(2))
                ->setEmail('user2@example.com')
                ->setFirstName('user2'),
        ];

        $this->indexer->expects($this->once())
            ->method('simpleSearch')
            ->with('query', 0, 101, ['oro_user'], 1)
            ->will($this->returnValue(new Result(new Query(), $items)));

        $this->entityRepository->expects($this->once())
            ->method('findById')
            ->with([1, 2])
            ->will($this->returnValue($users));

        $this->attendeeRelationManager->expects($this->exactly(2))
            ->method('createAttendee')
            ->withConsecutive(
                [$users[0]],
                [$users[1]]
            )
            ->will(
                $this->returnCallback(
                    function (User $user) {
                        return (new Attendee)
                            ->setUser($user)
                            ->setDisplayName($user->getFirstName())
                            ->setEmail($user->getEmail())
                            ->setStatus(new TestEnumValue('test', 'test'))
                            ->setType(new TestEnumValue('test', 'test'));
                    }
                )
            );

        $result = $this->attendeeSearchHandler->search('query', 1, 100);

        $this->assertEquals(
            [
                'results' => [
                    [
                        'id'          => json_encode(
                            [
                                'entityClass' => 'Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User',
                                'entityId'    => 1,
                            ]
                        ),
                        'text'        => 'user1',
                        'displayName' => 'user1',
                        'email'       => 'user1@example.com',
                        'status'      => 'test',
                        'type'        => 'test',
                    ],
                    [
                        'id'          => json_encode(
                            [
                                'entityClass' => 'Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User',
                                'entityId'    => 2,
                            ]
                        ),
                        'text'        => 'user2',
                        'displayName' => 'user2',
                        'email'       => 'user2@example.com',
                        'status'      => 'test',
                        'type'        => 'test',
                    ],
                ],
                'more'    => false,
            ],
            $result
        );
    }
}
