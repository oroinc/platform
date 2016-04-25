<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\EmailBundle\Model\EmailActivityUpdates;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwnerWithoutEmail as SecondEmailOwner;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;

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
                return ($entity instanceof TestEmailOwner || $entity instanceof SecondEmailOwner) &&
                    in_array($entity->getId(), $this->fixtures['ownersWithEmails']);
            }));

        $this->emailActivityUpdates = new EmailActivityUpdates(
            $emailOwnersProvider
        );
    }

    /**
     * @dataProvider testProvider
     */
    public function test(array $entities, array $expectedJobs)
    {
        $this->emailActivityUpdates->processUpdatedEmailAddresses($entities);
        $actualJobs = $this->emailActivityUpdates->createJobs();
        $this->assertCount(count($actualJobs), $expectedJobs);
        array_map([$this, 'assertJobs'], $expectedJobs, $actualJobs);
    }

    public function testProvider()
    {
        return [
            '0 email addresses' => [
                [],
                [],
            ],
            '3 email email addresses with 2 owners' => [
                [
                    (new EmailAddress())
                        ->setOwner(new TestEmailOwner(1)),
                    (new EmailAddress())
                        ->setOwner(new TestEmailOwner(2)),
                    (new EmailAddress())
                        ->setOwner(new TestEmailOwner(3)),
                    (new EmailAddress()),
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
            '4 email owners of 2 types of owners with emails' => [
                [
                    (new EmailAddress())
                        ->setOwner(new TestEmailOwner(1)),
                    (new EmailAddress())
                        ->setOwner(new TestEmailOwner(2)),
                    (new EmailAddress())
                        ->setOwner(new TestEmailOwner(3)),
                    (new EmailAddress())
                        ->setOwner(new SecondEmailOwner(1)),
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
                            'Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwnerWithoutEmail',
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
