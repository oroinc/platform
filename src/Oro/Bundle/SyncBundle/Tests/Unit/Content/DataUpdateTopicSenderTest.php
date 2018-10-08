<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Content;

use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\SyncBundle\Content\DataUpdateTopicSender;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class DataUpdateTopicSenderTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_USERNAME = 'usernameTeST';
    private const TEST_TAG1 = 'TestTag1';
    private const TEST_TAG2 = 'TestTag2';

    /**
     * @var WebsocketClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websocketClient;

    /**
     * @var ConnectionChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connectionChecker;

    /**
     * @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenStorage;

    /**
     * @var DataUpdateTopicSender
     */
    private $dataUpdateTopicSender;

    protected function setUp()
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

    public function testSendWithLoggedInUser()
    {
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(UserInterface::class);

        $this->tokenStorage
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $user
            ->expects(self::once())
            ->method('getUserName')
            ->willReturn(self::TEST_USERNAME);

        $this->connectionChecker
            ->expects(self::once())
            ->method('checkConnection')
            ->willReturn(true);

        $expectedMessage = [
            ['username' => self::TEST_USERNAME, 'tagname' => self::TEST_TAG1],
            ['username' => self::TEST_USERNAME, 'tagname' => self::TEST_TAG2],
        ];
        $this->websocketClient
            ->expects(self::once())
            ->method('publish')
            ->with('oro/data/update', $expectedMessage);

        $tags = [self::TEST_TAG1, self::TEST_TAG2];
        $this->dataUpdateTopicSender->send($tags);
    }

    public function testSendWithoutToken()
    {
        $this->tokenStorage
            ->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->connectionChecker
            ->expects(self::once())
            ->method('checkConnection')
            ->willReturn(true);

        $expectedMessage = [
            ['username' => null, 'tagname' => self::TEST_TAG1],
            ['username' => null, 'tagname' => self::TEST_TAG2]
        ];
        $this->websocketClient
            ->expects(self::once())
            ->method('publish')
            ->with('oro/data/update', $expectedMessage);

        $tags = [self::TEST_TAG1, self::TEST_TAG2];
        $this->dataUpdateTopicSender->send($tags);
    }

    public function testSendWhenTypeOfLoggedInUserIsNotSupported()
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token
            ->expects(self::once())
            ->method('getUser')
            ->willReturn(self::TEST_USERNAME);

        $this->connectionChecker
            ->expects(self::once())
            ->method('checkConnection')
            ->willReturn(true);

        $expectedMessage = [
            ['username' => null, 'tagname' => self::TEST_TAG1],
            ['username' => null, 'tagname' => self::TEST_TAG2]
        ];
        $this->websocketClient
            ->expects(self::once())
            ->method('publish')
            ->with('oro/data/update', $expectedMessage);

        $tags = [self::TEST_TAG1, self::TEST_TAG2];
        $this->dataUpdateTopicSender->send($tags);
    }

    public function testSendWithoutTags()
    {
        $this->tokenStorage
            ->expects(self::never())
            ->method('getToken');

        $this->connectionChecker
            ->expects(self::never())
            ->method('checkConnection');

        $this->websocketClient
            ->expects(self::never())
            ->method('publish');

        $this->dataUpdateTopicSender->send([]);
    }

    public function testSendNoConnection()
    {
        $this->tokenStorage
            ->expects(self::never())
            ->method('getToken');

        $this->connectionChecker
            ->expects(self::once())
            ->method('checkConnection')
            ->willReturn(false);

        $this->websocketClient
            ->expects(self::never())
            ->method('publish');

        $this->dataUpdateTopicSender->send([self::TEST_TAG1, self::TEST_TAG2]);
    }
}
