<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityExtendBundle\Mapping\ExtendClassMetadataFactory;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\CacheItem;

class ExtendClassMetadataFactoryTest extends TestCase
{
    private ExtendClassMetadataFactory $cmf;

    #[\Override]
    protected function setUp(): void
    {
        $this->cmf = new ExtendClassMetadataFactory();
    }

    public function testSetMetadataFor(): void
    {
        $classMetadata = new ClassMetadata(User::class);
        $cache = $this->createMock(ArrayAdapter::class);
        $cacheItem = new CacheItem();
        $cache->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);
        $cache->expects($this->once())
            ->method('save')
            ->with($cacheItem);
        $this->cmf->setCache($cache);
        $this->cmf->setMetadataFor(User::class, $classMetadata);

        $this->assertSame($classMetadata, $this->cmf->getMetadataFor(User::class));
    }

    public function testSetMetadataForWithoutCacheDriver(): void
    {
        $metadata = new ClassMetadata(User::class);
        $this->cmf->setMetadataFor(
            User::class,
            $metadata
        );
    }
}
