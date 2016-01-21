<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailEntity;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestThread;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;

class EmailThreadManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $emailThreadProvider;
    protected $em;

    protected $emailThreadManager;

    protected $fixtures = [];

    public function setUp()
    {
        $thread1 = new TestThread(1);
        $thread2 = new TestThread(2);
        $thread3 = new TestThread(3);
        $this->fixtures = [
            'emails' => [
                1 => new TestEmailEntity(1),
                2 => (new TestEmailEntity(2))
                        ->setThread($thread1)
                        ->setHead(false),
                3 => (new TestEmailEntity(3))
                        ->setThread($thread2),
                4 => (new TestEmailEntity(4))
                        ->setHead(false)
                        ->setThread($thread3),
                5 => (new TestEmailEntity(5))
                        ->setHead(true)
                        ->setThread($thread3),
            ],
            'threads' => [
                null => new TestThread(),
                1 => $thread1,
                2 => $thread2,
                3 => $thread3,
            ],
        ];

        $this->emailThreadProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailThreadManager = new EmailThreadManager($this->emailThreadProvider, $this->em);
    }

    /**
     * @dataProvider updateThreadsDataProvider
     */
    public function testUpdateThreads(array $newEmails, array $threadIds, array $expectedEmails)
    {
        $consecutiveThreads = array_map(
            function ($threadId) {
                return $this->findFixtureBy($threadId, 'threads');
            },
            $threadIds
        );

        $this->emailThreadProvider->expects($this->any())
            ->method('getEmailThread')
            ->will(call_user_func_array([$this, 'onConsecutiveCalls'], $consecutiveThreads));
        $this->emailThreadProvider->expects($this->any())
            ->method('getEmailReferences')
            ->will($this->returnValue([]));

        $this->emailThreadManager->updateThreads($newEmails);
        $this->assertEquals($expectedEmails, $newEmails);
    }

    public function updateThreadsDataProvider()
    {
        return [
            'new email without thread' => [
                [
                    new TestEmailEntity(),
                ],
                [
                    null,
                ],
                [
                    (new TestEmailEntity())
                        ->setThread(new TestThread()),
                ],
            ],
            'new email within thread' => [
                [
                    new TestEmailEntity(),
                ],
                [
                    1,
                ],
                [
                    (new TestEmailEntity())
                        ->setThread(new TestThread(1)),
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateHeadsDataProvider
     */
    public function testUpdateHeads(array $updatedEmails, array $expectedEmails)
    {
        $this->emailThreadProvider->expects($this->any())
            ->method('getThreadEmails')
            ->will($this->returnCallback(function ($em, TestEmailEntity $entity) {
                return array_merge(
                    [$entity],
                    array_filter($this->fixtures['emails'], function (TestEmailEntity $email) use ($entity) {
                        return $entity !== $email && $entity->getThread() == $email->getThread();
                    })
                );
            }));

        $this->emailThreadManager->updateHeads($updatedEmails);
        $this->assertEquals($expectedEmails, $updatedEmails);
    }

    public function updateHeadsDataProvider()
    {
        return [
            'new emails are not updated' => [
                [
                    (new TestEmailEntity())
                        ->setHead(false),
                    (new TestEmailEntity())
                        ->setHead(true)
                ],
                [
                    (new TestEmailEntity())
                        ->setHead(false),
                    (new TestEmailEntity())
                        ->setHead(true),
                ],
            ],
            'emails without thread are not updated' => [
                [
                    (new TestEmailEntity(4))
                        ->setHead(false),
                    (new TestEmailEntity(4))
                        ->setHead(true),
                ],
                [
                    (new TestEmailEntity(4))
                        ->setHead(false),
                    (new TestEmailEntity(4))
                        ->setHead(true),
                ],
            ],
            'updated email should be head' => [
                [
                    (new TestEmailEntity(4))
                        ->setThread(new TestThread(3))
                        ->setHead(false)
                ],
                [
                    (new TestEmailEntity(4))
                        ->setThread(new TestThread(3))
                        ->setHead(true)
                ],
            ],
        ];
    }

    protected function findFixtureBy($value, $key)
    {
        if (array_key_exists($key, $this->fixtures) && array_key_exists($value, $this->fixtures[$key])) {
            return $this->fixtures[$key][$value];
        }

        return null;
    }
}
