<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model\WebSocket;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Model\WebSocket\WebSocketSendProcessor;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;

class WebSocketSendProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var WebsocketClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $websocketClient;

    /**
     * @var ConnectionChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $connectionChecker;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $emailUser;

    /**
     * @var WebSocketSendProcessor
     */
    protected $processor;

    protected function setUp()
    {
        $this->websocketClient = $this->createMock(WebsocketClientInterface::class);
        $this->connectionChecker = $this->createMock(ConnectionChecker::class);
        $this->email = $this->createMock(Email::class);

        $this->emailUser = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailUser')
            ->setMethods(['getOwner', 'getOrganization'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new WebSocketSendProcessor($this->websocketClient, $this->connectionChecker);
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

        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(true);

        $this->websocketClient->expects($this->once())
            ->method('publish')
            ->with(
                WebSocketSendProcessor::getUserTopic($this->emailUser->getOwner(), $this->emailUser->getOrganization()),
                ['hasNewEmail' => true]
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

        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(true);

        $this->websocketClient->expects($this->once())
            ->method('publish')
            ->with(
                WebSocketSendProcessor::getUserTopic($this->emailUser->getOwner(), $this->emailUser->getOrganization()),
                ['hasNewEmail' => false]
            );

        $this->processor->send([1 => ['entity' => $this->emailUser, 'new' => 0]]);
    }

    public function testSendNoConnection()
    {
        $this->emailUser->expects($this->never())
            ->method($this->anything());

        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(false);

        $this->websocketClient->expects($this->never())
            ->method($this->anything());

        $this->processor->send([1 => ['entity' => $this->emailUser, 'new' => 0]]);
    }

    public function testSendFailure()
    {
        $this->websocketClient->expects($this->never())
            ->method('publish');

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
                'expected' => 'oro/email_event/123/321',
            ],
            'user is string' => [
                'user' => 'TEST',
                'organization' => $organization,
                'expected' => 'oro/email_event/TEST/321',
            ],
            'no organization' => [
                'user' => 'TEST',
                'organization' => null,
                'expected' => 'oro/email_event/TEST/*',
            ],
        ];
    }
}
