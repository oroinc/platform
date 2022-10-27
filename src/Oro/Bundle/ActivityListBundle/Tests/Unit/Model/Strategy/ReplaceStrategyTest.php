<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Model\Strategy;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityListBundle\Entity\Manager\ActivityListManager;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\ActivityListBundle\Model\MergeModes;
use Oro\Bundle\ActivityListBundle\Model\Strategy\ReplaceStrategy;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Acl\Util\ClassUtils;

class ReplaceStrategyTest extends \PHPUnit\Framework\TestCase
{
    /** @var ReplaceStrategy */
    private $strategy;

    /** @var ActivityListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $activityListManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ActivityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $activityManager;

    protected function setUp(): void
    {
        $activityListManager = ActivityListManager::class;
        $this->activityListManager = $this->createMock($activityListManager);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->activityManager = $this->createMock(ActivityManager::class);

        $this->strategy = new ReplaceStrategy(
            $this->activityListManager,
            $this->doctrineHelper,
            $this->activityManager
        );
    }

    public function testNotSupports()
    {
        $fieldData = new FieldData(new EntityData(new EntityMetadata(), []), new FieldMetadata());
        $fieldData->setMode(MergeModes::ACTIVITY_UNITE);

        $this->assertFalse($this->strategy->supports($fieldData));
    }

    public function testSupports()
    {
        $fieldData = new FieldData(new EntityData(new EntityMetadata(), []), new FieldMetadata());
        $fieldData->setMode(MergeModes::ACTIVITY_REPLACE);

        $this->assertTrue($this->strategy->supports($fieldData));
    }

    public function testMerge()
    {
        $account1 = new User();
        $account2 = new User();
        ReflectionUtil::setId($account1, 1);
        ReflectionUtil::setId($account2, 2);
        $entityMetadata = new EntityMetadata(['type' => ClassUtils::getRealClass($account1)]);
        $entityData = new EntityData($entityMetadata, [$account1, $account2]);
        $entityData->setMasterEntity($account1);
        $fieldData = new FieldData($entityData, new FieldMetadata());
        $fieldData->setMode(MergeModes::ACTIVITY_REPLACE);
        $fieldData->setSourceEntity($account2);

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->any())
            ->method('getResult')
            ->willReturn([
                ['id' => 1, 'relatedActivityId' => 11],
                ['id' => 3, 'relatedActivityId' => 2]
            ]);

        $repository = $this->createMock(ActivityListRepository::class);
        $repository->expects($this->any())
            ->method('getActivityListQueryBuilderByActivityClass')
            ->willReturn($queryBuilder);
        $repository->expects($this->any())
            ->method('findBy')
            ->willReturn([]);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($repository);

        $this->activityListManager->expects($this->exactly(2))
            ->method('replaceActivityTargetWithPlainQuery');

        $this->strategy->merge($fieldData);
    }

    public function testGetName()
    {
        $this->assertEquals('activity_replace', $this->strategy->getName());
    }
}
