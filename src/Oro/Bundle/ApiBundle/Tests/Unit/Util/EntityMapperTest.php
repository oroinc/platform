<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Model\SomeModel;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;

class EntityMapperTest extends OrmRelatedTestCase
{
    /** @var EntityOverrideProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $entityOverrideProvider;

    /** @var EntityMapper */
    private $entityMapper;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityOverrideProvider = $this->createMock(EntityOverrideProviderInterface::class);

        $this->entityMapper = new EntityMapper(
            $this->doctrineHelper,
            new EntityInstantiator($this->doctrineHelper),
            $this->entityOverrideProvider
        );

        $this->notManageableClassNames = [UserProfile::class];
    }

    public function testGetModelForEnumOption(): void
    {
        $entity = new EnumOption('test.1', 'test', 'Item 1', '1');
        $modelClass = 'Extend\Entity\EV_Test_Enum';

        $this->entityOverrideProvider->expects(self::never())
            ->method('getSubstituteEntityClass');

        /** @var EnumOption $model */
        $model = $this->entityMapper->getModel($entity, $modelClass);

        self::assertInstanceOf(EnumOption::class, $model);
        self::assertEquals($entity->getId(), $model->getId());
    }

    public function testGetModelWithEmptyAssociations(): void
    {
        $entity = new User();
        $entity->setId(123);
        $modelClass = UserProfile::class;

        $this->entityOverrideProvider->expects(self::never())
            ->method('getSubstituteEntityClass');

        /** @var UserProfile $model */
        $model = $this->entityMapper->getModel($entity, $modelClass);

        self::assertInstanceOf($modelClass, $model);
        self::assertEquals($entity->getId(), $model->getId());
    }

    public function testGetEntityWithEmptyAssociations(): void
    {
        $model = new UserProfile();
        $model->setId(123);
        $entityClass = User::class;

        $this->entityOverrideProvider->expects(self::never())
            ->method('getSubstituteEntityClass');

        /** @var User $entity */
        $entity = $this->entityMapper->getEntity($model, $entityClass);

        self::assertInstanceOf($entityClass, $entity);
        self::assertNotInstanceOf(UserProfile::class, $entity);
        self::assertEquals($model->getId(), $entity->getId());
    }

    public function testGetModelWithNotEmptyToOneAssociation(): void
    {
        $entity = new User();
        $entity->setCategory(new Category('category1'));
        $modelClass = UserProfile::class;

        $this->entityOverrideProvider->expects(self::once())
            ->method('getSubstituteEntityClass')
            ->with(Category::class)
            ->willReturn(null);

        /** @var UserProfile $model */
        $model = $this->entityMapper->getModel($entity, $modelClass);

        self::assertInstanceOf($modelClass, $model);
        self::assertSame($entity->getCategory(), $model->getCategory());
    }

    public function testGetEntityWithNotEmptyToOneAssociation(): void
    {
        $model = new UserProfile();
        $model->setCategory(new Category('category1'));
        $entityClass = User::class;

        $this->entityOverrideProvider->expects(self::never())
            ->method('getSubstituteEntityClass');

        /** @var User $entity */
        $entity = $this->entityMapper->getEntity($model, $entityClass);

        self::assertInstanceOf($entityClass, $entity);
        self::assertNotInstanceOf(UserProfile::class, $entity);
        self::assertSame($model->getCategory(), $entity->getCategory());
    }

    public function testGetModelWhenItIsNotManageableEntity(): void
    {
        $entity = new SomeModel(123);

        $this->entityOverrideProvider->expects(self::never())
            ->method('getSubstituteEntityClass');

        $model = $this->entityMapper->getModel($entity, SomeModel::class);

        self::assertSame($entity, $model);
    }

    public function testGetEntityWhenItIsNotManageableEntity(): void
    {
        $model = new SomeModel(123);

        $this->entityOverrideProvider->expects(self::never())
            ->method('getSubstituteEntityClass');

        $entity = $this->entityMapper->getEntity($model, SomeModel::class);

        self::assertSame($model, $entity);
    }

    public function testGetModelWhenItIsNotManageableEntityAndPreregisteredEntity(): void
    {
        $entity = new SomeModel(123);

        $this->entityOverrideProvider->expects(self::never())
            ->method('getSubstituteEntityClass');

        $this->entityMapper->registerEntity($entity);
        $model = $this->entityMapper->getModel($entity, SomeModel::class);

        self::assertSame($entity, $model);
    }

    public function testGetEntityWhenItIsNotManageableEntityAndPreregisteredEntity(): void
    {
        $model = new SomeModel(123);

        $this->entityOverrideProvider->expects(self::never())
            ->method('getSubstituteEntityClass');

        $this->entityMapper->registerEntity($model);
        $entity = $this->entityMapper->getEntity($model, SomeModel::class);

        self::assertSame($model, $entity);
    }
}
