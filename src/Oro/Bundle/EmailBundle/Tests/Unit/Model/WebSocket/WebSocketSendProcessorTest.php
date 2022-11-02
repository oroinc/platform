<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model\WebSocket;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Model\WebSocket\WebSocketSendProcessor;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\UserBundle\Entity\User;

class WebSocketSendProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebsocketClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $websocketClient;

    /** @var ConnectionChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $connectionChecker;

    /** @var EmailUser|\PHPUnit\Framework\MockObject\MockObject */
    private $emailUser;

    /** @var WebSocketSendProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->websocketClient = $this->createMock(WebsocketClientInterface::class);
        $this->connectionChecker = $this->createMock(ConnectionChecker::class);
        $this->emailUser = $this->createMock(EmailUser::class);

        $this->processor = new WebSocketSendProcessor($this->websocketClient, $this->connectionChecker);
    }

    public function testSendSuccess()
    {
        $organization = new Organization();
        $organization->setId(1);
        $user = new User();
        $user->setId(1);

        $this->emailUser->expects($this->once())
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

        $this->emailUser->expects($this->once())
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
     */
    public function testGetUserTopic(User|string $user, ?Organization $organization, string $expected)
    {
        $this->assertEquals($expected, WebSocketSendProcessor::getUserTopic($user, $organization));
    }

    public function getUserTopicDataProvider(): array
    {
        $organization = new Organization();
        $organization->setId(321);
        $user = new User();
        $user->setId(123);

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
