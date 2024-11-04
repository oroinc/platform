<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityExtendBundle\Mapping\ExtendClassMetadataFactory;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\CacheItem;

class ExtendClassMetadataFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExtendClassMetadataFactory */
    private $cmf;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->cmf = new ExtendClassMetadataFactory();
    }

    public function testSetMetadataFor()
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

    public function testSetMetadataForWithoutCacheDriver()
    {
        $metadata = new ClassMetadata(User::class);
        $this->cmf->setMetadataFor(
            User::class,
            $metadata
        );
    }
}
