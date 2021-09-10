<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Annotation\Loader\AclAnnotationLoaderInterface;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationStorage;
use Oro\Component\Testing\TempDirExtension;

class AclAnnotationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $loader;

    /** @var AclAnnotationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->loader = $this->createMock(AclAnnotationLoaderInterface::class);

        $cacheFile = $this->getTempFile('AclAnnotationProvider');

        $this->provider = new AclAnnotationProvider(
            $cacheFile,
            false,
            $this->entityClassResolver,
            [$this->loader]
        );
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
            ->willReturnCallback(function ($storage) {
                /** @var AclAnnotationStorage $storage */
                $storage->add(
                    new AclAnnotation(['id' => 'test', 'type' => 'entity']),
                    \stdClass::class,
                    'SomeMethod'
                );
            });

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
}
