<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\WebSocket;

use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\EntityConfigBundle\WebSocket\AttributesImportTopicSender;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributesImportTopicSenderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const CONFIG_MODEL_ID = 33;
    const USER_ID = 44;

    /**
     * @var TopicPublisher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $publisher;

    /**
     * @var TokenAccessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenAccessor;

    /**
     * @var AttributesImportTopicSender
     */
    protected $attributesImportTopicSender;

    protected function setUp()
    {
        $this->publisher = $this->createMock(TopicPublisher::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->attributesImportTopicSender = new AttributesImportTopicSender($this->publisher, $this->tokenAccessor);
    }

    public function testGetTopicWhenNoUser()
    {
        $this->tokenAccessor
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can not get current user');

        $this->attributesImportTopicSender->getTopic(self::CONFIG_MODEL_ID);
    }

    public function testGetTopicWhenNotIntegerConfigModelId()
    {
        $this->tokenAccessor
            ->expects($this->never())
            ->method('getUser');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument configModelId should be of integer type');

        $this->attributesImportTopicSender->getTopic('something');
    }

    public function testGetTopic()
    {
        $user = $this->getEntity(User::class, ['id' => self::USER_ID]);
        $this->tokenAccessor
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->assertEquals(
            sprintf(AttributesImportTopicSender::TOPIC, self::USER_ID, self::CONFIG_MODEL_ID),
            $this->attributesImportTopicSender->getTopic(self::CONFIG_MODEL_ID)
        );
    }

    public function testSend()
    {
        $user = $this->getEntity(User::class, ['id' => self::USER_ID]);

        $this->tokenAccessor
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->publisher
            ->expects($this->once())
            ->method('send')
            ->with(
                sprintf(AttributesImportTopicSender::TOPIC, self::USER_ID, self::CONFIG_MODEL_ID),
                json_encode(['finished' => true])
            );

        $this->attributesImportTopicSender->send(self::CONFIG_MODEL_ID);
    }
}
