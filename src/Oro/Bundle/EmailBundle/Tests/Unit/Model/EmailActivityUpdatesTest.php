<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\EmailBundle\Model\EmailActivityUpdates;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmail;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestThread;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class EmailActivityUpdatesTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailActivityUpdates */
    protected $emailActivityUpdates;

    /** @var array */
    protected $fixtures;

    public function setUp()
    {
        $this->fixtures = [
            'ownersWithEmails' => [1, 2],
        ];

        $emailOwnersProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $emailOwnersProvider->expects($this->any())
            ->method('hasEmailsByOwnerEntity')
            ->will($this->returnCallback(function ($entity) {
                return ($entity instanceof TestEmailOwner || $entity instanceof TestThread) &&
                    in_array($entity->getId(), $this->fixtures['ownersWithEmails']);
            }));

        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->emailActivityUpdates = new EmailActivityUpdates(
            $emailOwnersProvider,
            new DoctrineHelper($registry)
        );
    }

    /**
     * @dataProvider testProvider
     */
    public function test(array $entities, array $expectedJobs)
    {
        $this->emailActivityUpdates->processCreatedEntities($entities);
        $actualJobs = $this->emailActivityUpdates->createJobs();
        $this->assertCount(count($actualJobs), $expectedJobs);
        array_map([$this, 'assertJobs'], $expectedJobs, $actualJobs);
    }

    public function testProvider()
    {
        return [
            '0 email owners with emails' => [
                [
                    new TestEmailOwner(3),
                    new TestEmail(4),
                    new TestEmail(2),
                ],
                [],
            ],
            '2 email owners with emails' => [
                [
                    new TestEmailOwner(1),
                    new TestEmailOwner(2),
                    new TestEmailOwner(3),
                    new TestEmail(4),
                    new TestEmail(2),
                ],
                [
                    new Job(
                        'oro:email:update-email-owner-associations',
                        [
                            'Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner',
                            1,
                            2,
                        ]
                    ),
                ],
            ],
            '3 email owners of 2 types with emails' => [
                [
                    new TestEmailOwner(1),
                    new TestEmailOwner(2),
                    new TestEmailOwner(3),
                    new TestThread(1),
                    new TestEmail(4),
                    new TestEmail(2),
                ],
                [
                    new Job(
                        'oro:email:update-email-owner-associations',
                        [
                            'Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner',
                            1,
                            2,
                        ]
                    ),
                    new Job(
                        'oro:email:update-email-owner-associations',
                        [
                            'Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestThread',
                            1,
                        ]
                    ),
                ],
            ],
        ];
    }

    /**
     * @param Job $expected
     * @param Job $actual
     */
    protected function assertJobs(Job $expected, Job $actual)
    {
        $this->assertEquals($expected->getCommand(), $actual->getCommand());
        $this->assertEquals($expected->getArgs(), $actual->getArgs());
    }
}
