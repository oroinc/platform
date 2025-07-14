<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\EventListener\AttributeManagerCacheListener;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for AttributeManagerCacheListener
 */
class AttributeManagerCacheListenerTest extends TestCase
{
    private AttributeManagerCacheListener $listener;
    private AttributeManager&MockObject $attributeManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->attributeManager = $this->createMock(AttributeManager::class);
        $this->listener = new AttributeManagerCacheListener($this->attributeManager);
    }

    public function testOnCreateEntity(): void
    {
        $this->attributeManager->expects(self::once())
            ->method('clearAttributesCache');

        $this->listener->onCreateEntity();
    }

    public function testOnCreateField(): void
    {
        $this->attributeManager->expects(self::once())
            ->method('clearAttributesCache');

        $this->listener->onCreateField();
    }

    public function testOnUpdateEntity(): void
    {
        $this->attributeManager->expects(self::once())
            ->method('clearAttributesCache');

        $this->listener->onUpdateEntity();
    }

    public function testOnUpdateField(): void
    {
        $this->attributeManager->expects(self::once())
            ->method('clearAttributesCache');

        $this->listener->onUpdateField();
    }

    public function testOnRenameField(): void
    {
        $this->attributeManager->expects(self::once())
            ->method('clearAttributesCache');

        $this->listener->onRenameField();
    }
}
