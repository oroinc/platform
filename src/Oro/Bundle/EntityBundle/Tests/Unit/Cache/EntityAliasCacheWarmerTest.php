<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Cache;

use Oro\Bundle\EntityBundle\Cache\EntityAliasCacheWarmer;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

class EntityAliasCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityAliasResolver;

    /** @var EntityAliasCacheWarmer */
    protected $cacheWarmer;

    protected function setUp()
    {
        $this->entityAliasResolver = $this->createMock(EntityAliasResolver::class);

        $this->cacheWarmer = new EntityAliasCacheWarmer($this->entityAliasResolver);
    }

    public function testWarmUp()
    {
        $cacheDir = 'test';

        $this->entityAliasResolver->expects($this->once())
            ->method('warmUpCache');

        $this->cacheWarmer->warmUp($cacheDir);
    }

    public function testIsOptional()
    {
        $this->assertFalse($this->cacheWarmer->isOptional());
    }
}
