<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Model;

use Oro\Bundle\DataAuditBundle\Model\EntityReference;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityReferenceTest extends TestCase
{
    public function testShouldCreateNullReference(): void
    {
        new EntityReference();
    }

    public function testShouldNullReferenceBeInitializedAsLoaded(): void
    {
        $reference = new EntityReference();
        self::assertTrue($reference->isLoaded());
    }

    public function testShouldClassNameForNullReferenceBeNull(): void
    {
        $reference = new EntityReference();
        self::assertNull($reference->getClassName());
    }

    public function testShouldIdForNullReferenceBeNull(): void
    {
        $reference = new EntityReference();
        self::assertNull($reference->getId());
    }

    public function testShouldEntityForNullReferenceBeNull(): void
    {
        $reference = new EntityReference();
        self::assertNull($reference->getEntity());
    }

    public function testShouldNotBePossibleToSetEntityForNullReference(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('An entity cannot be set to "null" reference object.');

        $reference = new EntityReference();
        $reference->setEntity(new \stdClass());
    }

    public function testShouldCreateReference(): void
    {
        new EntityReference('Test\Class', 'testId');
    }

    public function testShouldReferenceBeInitializedAsNotLoaded(): void
    {
        $reference = new EntityReference('Test\Class', 'testId');
        self::assertFalse($reference->isLoaded());
    }

    public function testShouldClassNameBeInitialized(): void
    {
        $reference = new EntityReference('Test\Class', 'testId');
        self::assertEquals('Test\Class', $reference->getClassName());
    }

    public function testShouldIdBeInitialized(): void
    {
        $reference = new EntityReference('Test\Class', 'testId');
        self::assertEquals('testId', $reference->getId());
    }

    public function testShouldThrowExceptionIfEntityIsNotLoadedYet(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity is not loaded yet. Call "setEntity" method before.');

        $reference = new EntityReference('Test\Class', 'testId');
        $reference->getEntity();
    }

    public function testShouldBePossibleToSetEntity(): void
    {
        $entity = new \stdClass();
        $reference = new EntityReference(get_class($entity), 'testId');
        $reference->setEntity($entity);
        self::assertSame($entity, $reference->getEntity());
    }

    public function testShouldBePossibleToSetInheritedEntity(): void
    {
        $entityClass = get_class(new \stdClass());
        $entity = $this->createMock($entityClass);

        // guard
        self::assertNotEquals($entityClass, get_class($entity));
        self::assertInstanceOf($entityClass, $entity);

        $reference = new EntityReference($entityClass, 'testId');
        $reference->setEntity($entity);
        self::assertSame($entity, $reference->getEntity());
    }

    public function testShouldBePossibleToSetNullEntity(): void
    {
        $reference = new EntityReference(get_class(new \stdClass()), 'testId');
        $reference->setEntity(null);
        self::assertNull($reference->getEntity());
    }

    public function testShouldSetEntityThrowExceptionIfNotObjectPassed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected argument of type "null or instance of Test\Class", "integer" given.');

        $reference = new EntityReference('Test\Class', 'testId');
        $reference->setEntity(123);
    }

    public function testShouldSetEntityThrowExceptionIfInvalidEntityTypePassed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected argument of type "null or instance of stdClass", "%s" given.',
            EntityReference::class
        ));

        $reference = new EntityReference(get_class(new \stdClass()), 'testId');
        $reference->setEntity(new EntityReference());
    }

    public function testShouldNotBePossibleToChangeAlreadySetEntity(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity is already loaded.');

        $entity = new \stdClass();
        $reference = new EntityReference(get_class($entity), 'testId');
        $reference->setEntity($entity);
        // test
        $reference->setEntity($entity);
    }
}
