<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationStorage;

class AclAnnotationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityClassResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $cache;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $loader;

    /** @var AclAnnotationProvider */
    protected $provider;

    protected function setUp()
    {
        $this->entityClassResolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cache = $this->getMockForAbstractClass(
            'Doctrine\Common\Cache\CacheProvider',
            array(),
            '',
            false,
            true,
            true,
            array('fetch', 'save', 'delete', 'deleteAll')
        );
        $this->loader = $this->createMock('Oro\Bundle\SecurityBundle\Annotation\Loader\AclAnnotationLoaderInterface');
        $this->provider = new AclAnnotationProvider($this->entityClassResolver, $this->cache);
        $this->provider->addLoader($this->loader);
    }

    public function testFindAndGetAnnotation()
    {
        $this->loader->expects($this->once())
            ->method('load')
            ->willReturnCallback(function ($storage) {
                /** @var AclAnnotationStorage $storage */
                $storage->add(
                    new AclAnnotation(['id' => 'test', 'type' => 'entity', 'class' => 'Test:Entity']),
                    \stdClass::class,
                    'SomeMethod'
                );
            });
        $this->entityClassResolver->expects(self::any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($className) {
                return str_replace(':', '\\', $className);
            });

        $foundAnnotation = $this->provider->findAnnotationById('test');
        $this->assertNotNull($foundAnnotation);
        $this->assertEquals('test', $foundAnnotation->getId());
        $this->assertEquals('Test\Entity', $foundAnnotation->getClass());
        $this->assertNull($this->provider->findAnnotationById('unknown'));

        $this->assertEquals('test', $this->provider->findAnnotation(\stdClass::class, 'SomeMethod')->getId());
        $this->assertNull($this->provider->findAnnotation(\stdClass::class, 'UnknownMethod'));
        $this->assertNull($this->provider->findAnnotation('UnknownClass', 'SomeMethod'));

        $this->assertCount(1, $this->provider->getAnnotations());
        $this->assertCount(1, $this->provider->getAnnotations('entity'));
        $this->assertCount(0, $this->provider->getAnnotations('action'));
    }

    public function testHasAnnotationAndIsProtected()
    {
        $this->loader->expects($this->once())
            ->method('load')
            ->will(
                $this->returnCallback(
                    function ($storage) {
                        /** @var AclAnnotationStorage $storage */
                        $storage->add(
                            new AclAnnotation(array('id' => 'test', 'type' => 'entity')),
                            \stdClass::class,
                            'SomeMethod'
                        );
                    }
                )
            );

        $this->assertFalse($this->provider->hasAnnotation(\stdClass::class));
        $this->assertFalse($this->provider->hasAnnotation('UnknownClass'));
        $this->assertTrue($this->provider->hasAnnotation(\stdClass::class, 'SomeMethod'));
        $this->assertFalse($this->provider->hasAnnotation(\stdClass::class, 'UnknownMethod'));
        $this->assertFalse($this->provider->hasAnnotation('UnknownClass', 'SomeMethod'));

        $this->assertTrue($this->provider->isProtectedClass(\stdClass::class));
        $this->assertFalse($this->provider->isProtectedClass('UnknownClass'));
        $this->assertTrue($this->provider->isProtectedMethod(\stdClass::class, 'SomeMethod'));
        $this->assertFalse($this->provider->isProtectedMethod(\stdClass::class, 'UnknownMethod'));
        $this->assertFalse($this->provider->isProtectedMethod('UnknownClass', 'SomeMethod'));
    }

    public function testCache()
    {
        // Called when: warmUpCache, findAnnotationById, warmUpCache
        $this->loader->expects($this->exactly(3))
            ->method('load');
        // First warmUpCache
        $this->cache->expects($this->at(0))
            ->method('save')
            ->with(AclAnnotationProvider::CACHE_KEY);
        // clearCache
        $this->cache->expects($this->at(1))
            ->method('delete')
            ->with(AclAnnotationProvider::CACHE_KEY);
        // First findAnnotationById
        $this->cache->expects($this->at(2))
            ->method('fetch')
            ->with(AclAnnotationProvider::CACHE_KEY);
        $this->cache->expects($this->at(3))
            ->method('save')
            ->with(AclAnnotationProvider::CACHE_KEY);
        // Second warmUpCache
        $this->cache->expects($this->at(4))
            ->method('save')
            ->with(AclAnnotationProvider::CACHE_KEY);

        $this->provider->warmUpCache();
        $this->provider->clearCache();
        $this->assertNull($this->provider->findAnnotationById('unknown'));
        $this->provider->warmUpCache();
        $this->assertNull($this->provider->findAnnotationById('unknown'));
    }
}
