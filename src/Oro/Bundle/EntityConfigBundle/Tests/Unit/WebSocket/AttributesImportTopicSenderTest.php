<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\WebSocket;

use Oro\Bundle\EntityConfigBundle\WebSocket\AttributesImportTopicSender;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttributesImportTopicSenderTest extends TestCase
{
    use EntityTrait;

    private const CONFIG_MODEL_ID = 33;
    private const USER_ID = 44;

    private WebsocketClientInterface&MockObject $websocketClient;
    private ConnectionChecker&MockObject $connectionChecker;
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private AttributesImportTopicSender $attributesImportTopicSender;

    #[\Override]
    protected function setUp(): void
    {
        $this->websocketClient = $this->createMock(WebsocketClientInterface::class);
        $this->connectionChecker = $this->createMock(ConnectionChecker::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->attributesImportTopicSender = new AttributesImportTopicSender(
            $this->websocketClient,
            $this->connectionChecker,
            $this->tokenAccessor
        );
    }

    public function testGetTopicWhenNoUser(): void
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can not get current user');

        $this->attributesImportTopicSender->getTopic(self::CONFIG_MODEL_ID);
    }

    public function testGetTopicWhenNotIntegerConfigModelId(): void
    {
        $this->tokenAccessor->expects($this->never())
            ->method('getUser');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument configModelId should be of integer type');

        $this->attributesImportTopicSender->getTopic('something');
    }

    public function testGetTopic(): void
    {
        $user = $this->getEntity(User::class, ['id' => self::USER_ID]);
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->assertEquals(
            sprintf(AttributesImportTopicSender::TOPIC, self::USER_ID, self::CONFIG_MODEL_ID),
            $this->attributesImportTopicSender->getTopic(self::CONFIG_MODEL_ID)
        );
    }

    public function testSend(): void
    {
        $user = $this->getEntity(User::class, ['id' => self::USER_ID]);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(true);

        $this->websocketClient->expects($this->once())
            ->method('publish')
            ->with(
                sprintf(AttributesImportTopicSender::TOPIC, self::USER_ID, self::CONFIG_MODEL_ID),
                ['finished' => true]
            );

        $this->attributesImportTopicSender->send(self::CONFIG_MODEL_ID);
    }

    public function testSendNoConnection(): void
    {
        $this->tokenAccessor->expects($this->never())
            ->method($this->anything());

        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(false);

        $this->websocketClient->expects($this->never())
            ->method($this->anything());

        $this->attributesImportTopicSender->send(self::CONFIG_MODEL_ID);
    }
}
