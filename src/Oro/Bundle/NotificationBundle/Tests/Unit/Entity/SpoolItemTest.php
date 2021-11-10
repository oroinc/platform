<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Entity;

use Oro\Bundle\NotificationBundle\Entity\SpoolItem;

class SpoolItemTest extends \PHPUnit\Framework\TestCase
{
    private SpoolItem $entity;

    protected function setUp(): void
    {
        $this->entity = new SpoolItem();

        // get id should return null cause this entity was not loaded from DB
        $this->assertNull($this->entity->getId());
    }

    public function testSetterGetterStatus()
    {
        // empty from construct
        $this->assertNull($this->entity->getStatus());
        $this->entity->setStatus('test.new.status');
        $this->assertEquals('test.new.status', $this->entity->getStatus());
    }

    public function testSetterGetterForMessage()
    {
        // empty from construct
        $this->assertNull($this->entity->getMessage());

        $message = $this->createMock(\Swift_Mime_SimpleMessage::class);

        $this->entity->setMessage($message);
        $this->assertEquals($message, $this->entity->getMessage());
    }

    public function testSetterGetterLogType()
    {
        // empty from construct
        $this->assertNull($this->entity->getLogType());
        $this->entity->setLogType('log type');
        $this->assertEquals('log type', $this->entity->getLogType());
    }
}
