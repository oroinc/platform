<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Cache;

use Oro\Bundle\EntityBundle\Cache\EntityAliasCacheWarmer;

class EntityAliasCacheWarmerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityAliasResolver;

    /** @var EntityAliasCacheWarmer */
    protected $cacheWarmer;

    protected function setUp()
    {
        $this->entityAliasResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
            ->disableOriginalConstructor()
            ->getMock();

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
        $this->assertTrue($this->cacheWarmer->isOptional());
    }
}
