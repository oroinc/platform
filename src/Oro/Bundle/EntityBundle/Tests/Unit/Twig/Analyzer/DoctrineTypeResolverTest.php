<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Analyzer;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\Tests\Unit\Stub\Entity1;
use Oro\Bundle\EntityBundle\Tests\Unit\Stub\EntityWithCategoryPropertyStub;
use Oro\Bundle\EntityBundle\Tests\Unit\Stub\EntityWithSnakeCasePropertyStub;
use Oro\Bundle\EntityBundle\Twig\Analyzer\DoctrineTypeResolver;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessEntry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Template;

final class DoctrineTypeResolverTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;

    private DoctrineTypeResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->resolver = new DoctrineTypeResolver($this->doctrine);
    }

    public function testResolveReturnsNullImmediatelyForArrayCallType(): void
    {
        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $result = $this->resolver->resolve('SomeClass', 'attribute', Template::ARRAY_CALL);

        self::assertNull($result);
    }

    public function testResolveReturnsNullWhenNoEntityManagerFoundForClass(): void
    {
        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with('SomeClass')
            ->willReturn(null);

        $result = $this->resolver->resolve('SomeClass', 'attribute', Template::ANY_CALL);

        self::assertNull($result);
    }

    public function testResolveReturnsNullForMethodCallWhenAttributeDoesNotStartWithGet(): void
    {
        $entityManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with('SomeClass')
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with('SomeClass')
            ->willReturn($classMetadata);

        $result = $this->resolver->resolve('SomeClass', 'create', Template::METHOD_CALL);

        self::assertNull($result);
    }

    public function testResolveReturnsNullForMethodCallWhenAttributeIsExactlyGet(): void
    {
        $className = Entity1::class;
        $entityManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($classMetadata);

        // "get" alone produces an empty property name after lcfirst(substr('get', 3)) = ''
        // and ReflectionClass::hasProperty('') returns false, so null is returned.
        $result = $this->resolver->resolve($className, 'get', Template::METHOD_CALL);

        self::assertNull($result);
    }

    public function testResolveReturnsNullForMethodCallWhenPropertyDerivedFromGetterDoesNotExistOnClass(): void
    {
        $className = Entity1::class;
        $entityManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($classMetadata);

        $classMetadata
            ->expects(self::never())
            ->method('hasAssociation');

        // Entity1 has no properties, so getCategory -> category does not exist
        $result = $this->resolver->resolve($className, 'getCategory', Template::METHOD_CALL);

        self::assertNull($result);
    }

    public function testResolveReturnsMethodAccessWithTargetClassForMethodCallOnSingleAssociation(): void
    {
        $className = EntityWithCategoryPropertyStub::class;
        $targetClass = 'Acme\\Entity\\Category';
        $entityManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($classMetadata);

        $classMetadata
            ->expects(self::once())
            ->method('hasAssociation')
            ->with('category')
            ->willReturn(true);

        $classMetadata
            ->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('category')
            ->willReturn($targetClass);

        $classMetadata
            ->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('category')
            ->willReturn(false);

        $result = $this->resolver->resolve($className, 'getCategory', Template::METHOD_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_METHOD, $result->accessType);
        self::assertSame($targetClass, $result->entityClass);
        self::assertFalse($result->isCollection);
        self::assertSame('getCategory', $result->attributeName);
        self::assertFalse($result->skipAccessEntry);
    }

    public function testResolveReturnsMethodAccessWithCollectionFlagForMethodCallOnCollectionAssociation(): void
    {
        $className = EntityWithCategoryPropertyStub::class;
        $targetClass = 'Acme\\Entity\\Category';
        $entityManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($classMetadata);

        $classMetadata
            ->expects(self::once())
            ->method('hasAssociation')
            ->with('category')
            ->willReturn(true);

        $classMetadata
            ->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('category')
            ->willReturn($targetClass);

        $classMetadata
            ->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('category')
            ->willReturn(true);

        $result = $this->resolver->resolve($className, 'getCategory', Template::METHOD_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_METHOD, $result->accessType);
        self::assertSame($targetClass, $result->entityClass);
        self::assertTrue($result->isCollection);
        self::assertSame('getCategory', $result->attributeName);
        self::assertFalse($result->skipAccessEntry);
    }

    public function testResolveReturnsMethodAccessWithNullTargetForMethodCallOnNonAssociationField(): void
    {
        $className = EntityWithCategoryPropertyStub::class;
        $entityManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($classMetadata);

        $classMetadata
            ->expects(self::once())
            ->method('hasAssociation')
            ->with('name')
            ->willReturn(false);

        $classMetadata
            ->expects(self::never())
            ->method('getAssociationTargetClass');

        $classMetadata
            ->expects(self::never())
            ->method('isCollectionValuedAssociation');

        $result = $this->resolver->resolve($className, 'getName', Template::METHOD_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_METHOD, $result->accessType);
        self::assertNull($result->entityClass);
        self::assertFalse($result->isCollection);
        self::assertSame('getName', $result->attributeName);
        self::assertFalse($result->skipAccessEntry);
    }

    public function testResolveReturnsPropertyAccessWithTargetClassForAnyCallOnSingleAssociation(): void
    {
        $className = EntityWithCategoryPropertyStub::class;
        $targetClass = 'Acme\\Entity\\Category';
        $entityManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($classMetadata);

        $classMetadata
            ->expects(self::once())
            ->method('hasAssociation')
            ->with('category')
            ->willReturn(true);

        $classMetadata
            ->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('category')
            ->willReturn($targetClass);

        $classMetadata
            ->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('category')
            ->willReturn(false);

        $result = $this->resolver->resolve($className, 'category', Template::ANY_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $result->accessType);
        self::assertSame($targetClass, $result->entityClass);
        self::assertFalse($result->isCollection);
        self::assertSame('category', $result->attributeName);
        self::assertFalse($result->skipAccessEntry);
    }

    public function testResolveReturnsPropertyAccessWithCollectionFlagForAnyCallOnCollectionAssociation(): void
    {
        $className = EntityWithCategoryPropertyStub::class;
        $targetClass = 'Acme\\Entity\\Tag';
        $entityManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($classMetadata);

        $classMetadata
            ->expects(self::once())
            ->method('hasAssociation')
            ->with('tags')
            ->willReturn(true);

        $classMetadata
            ->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('tags')
            ->willReturn($targetClass);

        $classMetadata
            ->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('tags')
            ->willReturn(true);

        $result = $this->resolver->resolve($className, 'tags', Template::ANY_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $result->accessType);
        self::assertSame($targetClass, $result->entityClass);
        self::assertTrue($result->isCollection);
        self::assertSame('tags', $result->attributeName);
        self::assertFalse($result->skipAccessEntry);
    }

    public function testResolveReturnsPropertyAccessWithNullTargetForAnyCallOnNonAssociationField(): void
    {
        $className = EntityWithCategoryPropertyStub::class;
        $entityManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($classMetadata);

        $classMetadata
            ->expects(self::once())
            ->method('hasAssociation')
            ->with('name')
            ->willReturn(false);

        $classMetadata
            ->expects(self::never())
            ->method('getAssociationTargetClass');

        $classMetadata
            ->expects(self::never())
            ->method('isCollectionValuedAssociation');

        $result = $this->resolver->resolve($className, 'name', Template::ANY_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $result->accessType);
        self::assertNull($result->entityClass);
        self::assertFalse($result->isCollection);
        self::assertSame('name', $result->attributeName);
        self::assertFalse($result->skipAccessEntry);
    }

    /**
     * @dataProvider nonArrayCallTypeProvider
     */
    public function testResolveReturnsNullForAnyNonArrayCallWhenManagerNotFound(string $callType): void
    {
        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with('SomeClass')
            ->willReturn(null);

        $result = $this->resolver->resolve('SomeClass', 'attribute', $callType);

        self::assertNull($result);
    }

    public static function nonArrayCallTypeProvider(): iterable
    {
        yield 'method call type' => [Template::METHOD_CALL];
    }

    public function testResolveReturnsPropertyAccessWithTargetClassForAnyCallWithSnakeCaseAttribute(): void
    {
        $className = EntityWithSnakeCasePropertyStub::class;
        $targetClass = 'Acme\\Entity\\Status';
        $entityManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($classMetadata);

        // The entity has $password_expires_at directly, so resolvePropertyName returns it as-is.
        $classMetadata
            ->expects(self::once())
            ->method('hasAssociation')
            ->with('password_expires_at')
            ->willReturn(true);

        $classMetadata
            ->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('password_expires_at')
            ->willReturn($targetClass);

        $classMetadata
            ->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('password_expires_at')
            ->willReturn(false);

        $result = $this->resolver->resolve($className, 'password_expires_at', Template::ANY_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $result->accessType);
        self::assertSame($targetClass, $result->entityClass);
        self::assertFalse($result->isCollection);
        self::assertSame('password_expires_at', $result->attributeName);
        self::assertFalse($result->skipAccessEntry);
    }

    public function testResolveReturnsPropertyAccessWithTargetClassForAnyCallWithCamelCaseAttribute(): void
    {
        $className = EntityWithSnakeCasePropertyStub::class;
        $targetClass = 'Acme\\Entity\\Status';
        $entityManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($classMetadata);

        // camelCase input 'passwordExpiresAt': resolvePropertyName finds the snake_case fallback
        // $password_expires_at and returns 'password_expires_at' as the resolved name.
        $classMetadata
            ->expects(self::once())
            ->method('hasAssociation')
            ->with('password_expires_at')
            ->willReturn(true);

        $classMetadata
            ->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('password_expires_at')
            ->willReturn($targetClass);

        $classMetadata
            ->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('password_expires_at')
            ->willReturn(false);

        $result = $this->resolver->resolve($className, 'passwordExpiresAt', Template::ANY_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_PROPERTY, $result->accessType);
        self::assertSame($targetClass, $result->entityClass);
        self::assertFalse($result->isCollection);
        // attributeName is the resolved PHP property name (snake_case), not the original Twig attribute
        self::assertSame('password_expires_at', $result->attributeName);
        self::assertFalse($result->skipAccessEntry);
    }

    public function testResolveReturnsMethodAccessWithNullTargetForMethodCallWhenGetterNameMapsToSnakeCase(): void
    {
        $className = EntityWithSnakeCasePropertyStub::class;
        $entityManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($classMetadata);

        // Getter getPasswordExpiresAt() -> derived 'passwordExpiresAt'; the entity only has
        // the snake_case property $password_expires_at, so resolvePropertyName returns it.
        // That property has no association.
        $classMetadata
            ->expects(self::once())
            ->method('hasAssociation')
            ->with('password_expires_at')
            ->willReturn(false);

        $result = $this->resolver->resolve($className, 'getPasswordExpiresAt', Template::METHOD_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_METHOD, $result->accessType);
        self::assertNull($result->entityClass);
        self::assertFalse($result->isCollection);
        self::assertSame('getPasswordExpiresAt', $result->attributeName);
        self::assertFalse($result->skipAccessEntry);
    }

    public function testResolveReturnsMethodAccessWithTargetClassForMethodCallWhenGetterNameMapsToSnakeCase(): void
    {
        $className = EntityWithSnakeCasePropertyStub::class;
        $targetClass = 'Acme\\Entity\\Status';
        $entityManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($classMetadata);

        // Getter getPasswordExpiresAt() -> derived 'passwordExpiresAt'; entity has $password_expires_at.
        // The association is also named 'password_expires_at'.
        $classMetadata
            ->expects(self::once())
            ->method('hasAssociation')
            ->with('password_expires_at')
            ->willReturn(true);

        $classMetadata
            ->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('password_expires_at')
            ->willReturn($targetClass);

        $classMetadata
            ->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('password_expires_at')
            ->willReturn(false);

        $result = $this->resolver->resolve($className, 'getPasswordExpiresAt', Template::METHOD_CALL);

        self::assertNotNull($result);
        self::assertSame(TemplateAccessEntry::ACCESS_TYPE_METHOD, $result->accessType);
        self::assertSame($targetClass, $result->entityClass);
        self::assertFalse($result->isCollection);
        self::assertSame('getPasswordExpiresAt', $result->attributeName);
        self::assertFalse($result->skipAccessEntry);
    }

    public function testResolveReturnsNullForMethodCallWhenGetterNameMapsToNeitherCamelNorSnakeCaseProperty(): void
    {
        $className = EntityWithSnakeCasePropertyStub::class;
        $entityManager = $this->createMock(ObjectManager::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($classMetadata);

        $classMetadata
            ->expects(self::never())
            ->method('hasAssociation');

        // EntityWithSnakeCasePropertyStub only has $password_expires_at; getCategory -> category
        // has no match in either camelCase or snake_case form.
        $result = $this->resolver->resolve($className, 'getCategory', Template::METHOD_CALL);

        self::assertNull($result);
    }
}
