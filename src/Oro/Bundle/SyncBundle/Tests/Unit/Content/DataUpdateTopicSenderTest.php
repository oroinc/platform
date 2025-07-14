<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Content;

use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\SyncBundle\Content\DataUpdateTopicSender;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class DataUpdateTopicSenderTest extends TestCase
{
    private const TEST_USERNAME = 'usernameTeST';
    private const TEST_TAG1 = 'TestTag1';
    private const TEST_TAG2 = 'TestTag2';

    private WebsocketClientInterface&MockObject $websocketClient;
    private ConnectionChecker&MockObject $connectionChecker;
    private TokenStorageInterface&MockObject $tokenStorage;
    private DataUpdateTopicSender $dataUpdateTopicSender;

    #[\Override]
    protected function setUp(): void
    {
        $this->websocketClient = $this->createMock(WebsocketClientInterface::class);
        $this->connectionChecker = $this->createMock(ConnectionChecker::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->dataUpdateTopicSender = new DataUpdateTopicSender(
            $this->websocketClient,
            $this->connectionChecker,
            $this->tokenStorage
        );
    }

    public function testSendWithLoggedInUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(UserInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $user->expects(self::once())
            ->method('getUserIdentifier')
            ->willReturn(self::TEST_USERNAME);

        $this->connectionChecker->expects(self::once())
            ->method('checkConnection')
            ->willReturn(true);

        $expectedMessage = [
            ['username' => self::TEST_USERNAME, 'tagname' => self::TEST_TAG1],
            ['username' => self::TEST_USERNAME, 'tagname' => self::TEST_TAG2],
        ];
        $this->websocketClient->expects(self::once())
            ->method('publish')
            ->with('oro/data/update', $expectedMessage);

        $tags = [self::TEST_TAG1, self::TEST_TAG2];
        $this->dataUpdateTopicSender->send($tags);
    }

    public function testSendWithoutToken(): void
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->connectionChecker->expects(self::once())
            ->method('checkConnection')
            ->willReturn(true);

        $expectedMessage = [
            ['username' => null, 'tagname' => self::TEST_TAG1],
            ['username' => null, 'tagname' => self::TEST_TAG2]
        ];
        $this->websocketClient->expects(self::once())
            ->method('publish')
            ->with('oro/data/update', $expectedMessage);

        $tags = [self::TEST_TAG1, self::TEST_TAG2];
        $this->dataUpdateTopicSender->send($tags);
    }

    public function testSendWhenTypeOfLoggedInUserIsNotSupported(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class));

        $this->connectionChecker->expects(self::once())
            ->method('checkConnection')
            ->willReturn(true);

        $expectedMessage = [
            ['username' => null, 'tagname' => self::TEST_TAG1],
            ['username' => null, 'tagname' => self::TEST_TAG2]
        ];
        $this->websocketClient->expects(self::once())
            ->method('publish')
            ->with('oro/data/update', $expectedMessage);

        $tags = [self::TEST_TAG1, self::TEST_TAG2];
        $this->dataUpdateTopicSender->send($tags);
    }

    public function testSendWithoutTags(): void
    {
        $this->tokenStorage->expects(self::never())
            ->method('getToken');

        $this->connectionChecker->expects(self::never())
            ->method('checkConnection');

        $this->websocketClient->expects(self::never())
            ->method('publish');

        $this->dataUpdateTopicSender->send([]);
    }

    public function testSendNoConnection(): void
    {
        $this->tokenStorage->expects(self::never())
            ->method('getToken');

        $this->connectionChecker->expects(self::once())
            ->method('checkConnection')
            ->willReturn(false);

        $this->websocketClient->expects(self::never())
            ->method('publish');

        $this->dataUpdateTopicSender->send([self::TEST_TAG1, self::TEST_TAG2]);
    }
}
