<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;

class GlobalScopeManagerTest extends AbstractScopeManagerTestCase
{
    #[\Override]
    protected function createManager(): GlobalScopeManager
    {
        return new GlobalScopeManager($this->doctrine, $this->cache, $this->dispatcher, $this->configBag);
    }

    #[\Override]
    protected function getScopedEntityName(): string
    {
        return 'app';
    }

    #[\Override]
    protected function getScopedEntity(): object
    {
        return new \stdClass();
    }

    #[\Override]
    public function testSetScopeId(): void
    {
        $this->dispatcher->expects(self::never())
            ->method('dispatch');

        $this->manager->setScopeId(123);

        self::assertSame(0, $this->manager->getScopeId());
    }

    #[\Override]
    public function testGetScopeIdFromEntity(): void
    {
        self::assertSame(0, $this->manager->getScopeIdFromEntity($this->getScopedEntity()));
    }

    #[\Override]
    public function testGetScopeIdFromUnsupportedEntity(): void
    {
        self::assertSame(0, $this->manager->getScopeIdFromEntity(new \stdClass()));
    }
}
