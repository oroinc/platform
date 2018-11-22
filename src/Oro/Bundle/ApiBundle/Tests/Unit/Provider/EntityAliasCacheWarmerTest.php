<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\EntityAliasCacheWarmer;
use Oro\Bundle\ApiBundle\Provider\EntityAliasResolverRegistry;

class EntityAliasCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityAliasResolverRegistry */
    private $entityAliasResolverRegistry;

    /** @var EntityAliasCacheWarmer */
    private $cacheWarmer;

    protected function setUp()
    {
        $this->entityAliasResolverRegistry = $this->createMock(EntityAliasResolverRegistry::class);

        $this->cacheWarmer = new EntityAliasCacheWarmer($this->entityAliasResolverRegistry);
    }

    public function testWarmUp()
    {
        $this->entityAliasResolverRegistry->expects(self::once())
            ->method('warmUpCache');

        $this->cacheWarmer->warmUp('test');
    }

    public function testIsOptional()
    {
        self::assertFalse($this->cacheWarmer->isOptional());
    }

    public function testWarmUpCache()
    {
        $this->entityAliasResolverRegistry->expects(self::once())
            ->method('warmUpCache');

        $this->cacheWarmer->warmUpCache();
    }

    public function testClearCache()
    {
        $this->entityAliasResolverRegistry->expects(self::once())
            ->method('clearCache');

        $this->cacheWarmer->clearCache();
    }
}
