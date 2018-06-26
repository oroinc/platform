<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ApiBundle\Util\EntityMapper;

class EntityMapperTest extends OrmRelatedTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityOverrideProviderInterface */
    private $entityOverrideProvider;

    /** @var EntityMapper */
    private $entityMapper;

    protected function setUp()
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

    public function testGetModelWithEmptyAssociations()
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

    public function testGetEntityWithEmptyAssociations()
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

    public function testGetModelWithNotEmptyToOneAssociation()
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

    public function testGetEntityWithNotEmptyToOneAssociation()
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
}
