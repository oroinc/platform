<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\EventListener\AttributeManagerCacheListener;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;

/**
 * Unit test for AttributeManagerCacheListener
 */
class AttributeManagerCacheListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttributeManagerCacheListener */
    private $listener;

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeManager;

    protected function setUp(): void
    {
        $this->attributeManager = $this->createMock(AttributeManager::class);
        $this->listener = new AttributeManagerCacheListener($this->attributeManager);
    }

    public function testOnCreateEntity()
    {
        $this->attributeManager->expects(self::once())
            ->method('clearAttributesCache');

        $this->listener->onCreateEntity();
    }

    public function testOnCreateField()
    {
        $this->attributeManager->expects(self::once())
            ->method('clearAttributesCache');

        $this->listener->onCreateField();
    }

    public function testOnUpdateEntity()
    {
        $this->attributeManager->expects(self::once())
            ->method('clearAttributesCache');

        $this->listener->onUpdateEntity();
    }

    public function testOnUpdateField()
    {
        $this->attributeManager->expects(self::once())
            ->method('clearAttributesCache');

        $this->listener->onUpdateField();
    }

    public function testOnRenameField()
    {
        $this->attributeManager->expects(self::once())
            ->method('clearAttributesCache');

        $this->listener->onRenameField();
    }
}
