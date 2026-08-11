<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityCreationTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitySelectOrCreateDataTransformerFactory;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity\TestEntity;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntitySelectOrCreateDataTransformerFactoryTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private AclHelper&MockObject $aclHelper;
    private EntitySelectOrCreateDataTransformerFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->factory = new EntitySelectOrCreateDataTransformerFactory($this->doctrine, $this->aclHelper);
    }

    public function testCreateTransformerWithoutNewItemPropertyName(): void
    {
        $transformer = $this->factory->createTransformer(TestEntity::class);

        self::assertInstanceOf(EntityToIdTransformer::class, $transformer);
        self::assertNotInstanceOf(EntityCreationTransformer::class, $transformer);
        self::assertSame($this->aclHelper, ReflectionUtil::getPropertyValue($transformer, 'aclHelper'));
        self::assertSame(TestEntity::class, ReflectionUtil::getPropertyValue($transformer, 'className'));
    }

    public function testCreateTransformerWhenCreateIsNotGranted(): void
    {
        $transformer = $this->factory->createTransformer(TestEntity::class, 'name', true, 'value', false);

        self::assertInstanceOf(EntityToIdTransformer::class, $transformer);
        self::assertNotInstanceOf(EntityCreationTransformer::class, $transformer);
        self::assertSame($this->aclHelper, ReflectionUtil::getPropertyValue($transformer, 'aclHelper'));
    }

    public function testCreateTransformerWhenNewItemPropertyNameIsSetAndCreateIsGranted(): void
    {
        $transformer = $this->factory->createTransformer(TestEntity::class, 'name', true, 'someValuePath');

        self::assertInstanceOf(EntityCreationTransformer::class, $transformer);
        self::assertSame($this->aclHelper, ReflectionUtil::getPropertyValue($transformer, 'aclHelper'));
        self::assertSame(TestEntity::class, ReflectionUtil::getPropertyValue($transformer, 'className'));
        self::assertSame('name', ReflectionUtil::getPropertyValue($transformer, 'newEntityPropertyName'));
        self::assertTrue(ReflectionUtil::getPropertyValue($transformer, 'allowEmptyProperty'));
        self::assertSame('someValuePath', ReflectionUtil::getPropertyValue($transformer, 'valuePath'));
    }

    public function testCreateTransformerAppliesNewItemDefaults(): void
    {
        $transformer = $this->factory->createTransformer(TestEntity::class, 'name');

        self::assertInstanceOf(EntityCreationTransformer::class, $transformer);
        self::assertSame($this->aclHelper, ReflectionUtil::getPropertyValue($transformer, 'aclHelper'));
        self::assertFalse(ReflectionUtil::getPropertyValue($transformer, 'allowEmptyProperty'));
        self::assertNull(ReflectionUtil::getPropertyValue($transformer, 'valuePath'));
    }

    public function testCreateTransformerWhenAclIsNotProtected(): void
    {
        $transformer = $this->factory->createTransformer(TestEntity::class, null, false, null, true, false);

        self::assertInstanceOf(EntityToIdTransformer::class, $transformer);
        self::assertNull(ReflectionUtil::getPropertyValue($transformer, 'aclHelper'));
    }

    public function testCreateTransformerWhenAclIsNotProtectedAndCreateIsGranted(): void
    {
        $transformer = $this->factory->createTransformer(TestEntity::class, 'name', false, null, true, false);

        self::assertInstanceOf(EntityCreationTransformer::class, $transformer);
        self::assertNull(ReflectionUtil::getPropertyValue($transformer, 'aclHelper'));
    }
}
