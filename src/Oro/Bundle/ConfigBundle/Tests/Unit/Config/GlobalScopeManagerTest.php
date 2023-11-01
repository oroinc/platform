<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;

class GlobalScopeManagerTest extends AbstractScopeManagerTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function createManager(): GlobalScopeManager
    {
        return new GlobalScopeManager($this->doctrine, $this->cache, $this->dispatcher, $this->configBag);
    }

    /**
     * {@inheritDoc}
     */
    protected function getScopedEntityName(): string
    {
        return 'app';
    }

    /**
     * {@inheritDoc}
     */
    protected function getScopedEntity(): object
    {
        return new \stdClass();
    }

    /**
     * {@inheritDoc}
     */
    public function testSetScopeId(): void
    {
        $this->dispatcher->expects(self::never())
            ->method('dispatch');

        $this->manager->setScopeId(123);

        self::assertSame(0, $this->manager->getScopeId());
    }

    /**
     * {@inheritDoc}
     */
    public function testGetScopeIdFromEntity(): void
    {
        self::assertSame(0, $this->manager->getScopeIdFromEntity($this->getScopedEntity()));
    }

    /**
     * {@inheritDoc}
     */
    public function testGetScopeIdFromUnsupportedEntity(): void
    {
        self::assertSame(0, $this->manager->getScopeIdFromEntity(new \stdClass()));
    }
}
