<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model\WebSocket;

use Oro\Bundle\EmailBundle\Model\WebSocket\WebSocketSendProcessor;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;

class WebSocketSendProcessorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $topicPublisher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailUser;

    /**
     * @var WebSocketSendProcessor
     */
    protected $processor;

    protected function setUp()
    {
        $this->topicPublisher = $this->getMockBuilder('Oro\Bundle\SyncBundle\Wamp\TopicPublisher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->email = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailUser = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailUser')
            ->setMethods(['getOwner', 'getOrganization'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new WebSocketSendProcessor($this->topicPublisher);
    }

    public function testSendSuccess()
    {
        $organization = new Organization();
        $organization->setId(1);
        $user = new User();
        $user->setId(1);

        $this->emailUser->expects($this->exactly(1))
            ->method('getOwner')
            ->willReturn($user);

        $this->emailUser->expects($this->exactly(2))
            ->method('getOrganization')
            ->willReturn($organization);

        $this->topicPublisher->expects($this->once())
            ->method('send')
            ->with(
                WebSocketSendProcessor::getUserTopic($this->emailUser->getOwner(), $this->emailUser->getOrganization()),
                json_encode(['hasNewEmail' => true])
            );

        $this->processor->send([1 => ['entity' => $this->emailUser, 'new' => 1]]);
    }

    public function testSendNotNewEntity()
    {
        $organization = new Organization();
        $organization->setId(1);
        $user = new User();
        $user->setId(1);

        $this->emailUser->expects($this->exactly(1))
            ->method('getOwner')
            ->willReturn($user);

        $this->emailUser->expects($this->exactly(2))
            ->method('getOrganization')
            ->willReturn($organization);

        $this->topicPublisher->expects($this->once())
            ->method('send')
            ->with(
                WebSocketSendProcessor::getUserTopic($this->emailUser->getOwner(), $this->emailUser->getOrganization()),
                json_encode(['hasNewEmail' => false])
            );

        $this->processor->send([1 => ['entity' => $this->emailUser, 'new' => 0]]);
    }

    public function testSendFailure()
    {
        $this->topicPublisher->expects($this->never())
            ->method('send');

        $this->processor->send([]);
    }

    /**
     * @dataProvider getUserTopicDataProvider
     *
     * @param User|string $user
     * @param Organization $organization
     * @param string $expected
     */
    public function testGetUserTopic($user, $organization, $expected)
    {
        $this->assertEquals($expected, WebSocketSendProcessor::getUserTopic($user, $organization));
    }

    /**
     * @return array
     */
    public function getUserTopicDataProvider()
    {
        $user = $this->getEntity(User::class, ['id' => 123]);
        $organization = $this->getEntity(Organization::class, ['id' => 321]);

        return [
            'user is object' => [
                'user' => $user,
                'organization' => $organization,
                'expected' => 'oro/email_event/user_123_org_321',
            ],
            'user is string' => [
                'user' => 'TEST',
                'organization' => $organization,
                'expected' => 'oro/email_event/user_TEST_org_321',
            ],
            'no organization' => [
                'user' => 'TEST',
                'organization' => null,
                'expected' => 'oro/email_event/user_TEST_org_',
            ],
        ];
    }
}
