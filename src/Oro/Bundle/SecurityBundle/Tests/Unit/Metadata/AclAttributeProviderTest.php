<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Attribute\Acl as AclAttribute;
use Oro\Bundle\SecurityBundle\Attribute\Loader\AclAttributeLoaderInterface;
use Oro\Bundle\SecurityBundle\Metadata\AclAttributeProvider;
use Oro\Bundle\SecurityBundle\Metadata\AclAttributeStorage;
use Oro\Component\Testing\TempDirExtension;

class AclAttributeProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $loader;

    /** @var AclAttributeProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->loader = $this->createMock(AclAttributeLoaderInterface::class);

        $cacheFile = $this->getTempFile('AclAttributeProvider');

        $this->provider = new AclAttributeProvider(
            $cacheFile,
            false,
            $this->entityClassResolver,
            [$this->loader]
        );
    }

    public function testFindAndGetAttribute()
    {
        $this->loader->expects($this->once())
            ->method('load')
            ->willReturnCallback(function ($storage) {
                /** @var AclAttributeStorage $storage */
                $storage->add(
                    AclAttribute::fromArray(['id' => 'test', 'type' => 'entity', 'class' => 'Test:Entity']),
                    \stdClass::class,
                    'SomeMethod'
                );
            });
        $this->entityClassResolver->expects(self::any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($className) {
                return str_replace(':', '\\', $className);
            });

        $foundAttribute = $this->provider->findAttributeById('test');
        $this->assertNotNull($foundAttribute);
        $this->assertEquals('test', $foundAttribute->getId());
        $this->assertEquals('Test\Entity', $foundAttribute->getClass());
        $this->assertNull($this->provider->findAttributeById('unknown'));

        $this->assertEquals('test', $this->provider->findAttribute(\stdClass::class, 'SomeMethod')->getId());
        $this->assertNull($this->provider->findAttribute(\stdClass::class, 'UnknownMethod'));
        $this->assertNull($this->provider->findAttribute('UnknownClass', 'SomeMethod'));

        $this->assertCount(1, $this->provider->getAttributes());
        $this->assertCount(1, $this->provider->getAttributes('entity'));
        $this->assertCount(0, $this->provider->getAttributes('action'));
    }

    public function testHasAttributeAndIsProtected()
    {
        $this->loader->expects($this->once())
            ->method('load')
            ->willReturnCallback(function ($storage) {
                /** @var AclAttributeStorage $storage */
                $storage->add(
                    AclAttribute::fromArray(['id' => 'test', 'type' => 'entity']),
                    \stdClass::class,
                    'SomeMethod'
                );
            });

        $this->assertFalse($this->provider->hasAttribute(\stdClass::class));
        $this->assertFalse($this->provider->hasAttribute('UnknownClass'));
        $this->assertTrue($this->provider->hasAttribute(\stdClass::class, 'SomeMethod'));
        $this->assertFalse($this->provider->hasAttribute(\stdClass::class, 'UnknownMethod'));
        $this->assertFalse($this->provider->hasAttribute('UnknownClass', 'SomeMethod'));

        $this->assertTrue($this->provider->isProtectedClass(\stdClass::class));
        $this->assertFalse($this->provider->isProtectedClass('UnknownClass'));
        $this->assertTrue($this->provider->isProtectedMethod(\stdClass::class, 'SomeMethod'));
        $this->assertFalse($this->provider->isProtectedMethod(\stdClass::class, 'UnknownMethod'));
        $this->assertFalse($this->provider->isProtectedMethod('UnknownClass', 'SomeMethod'));
    }
}
