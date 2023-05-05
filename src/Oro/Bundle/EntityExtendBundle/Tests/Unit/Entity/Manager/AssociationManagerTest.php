<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Entity\Manager;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Associations\TestOwner1;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Associations\TestOwner2;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Associations\TestTarget1;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Associations\TestTarget2;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

class AssociationManagerTest extends OrmTestCase
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var AssociationManager */
    private $associationManager;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);

        $aclHelperLink = $this->createMock(ServiceLink::class);
        $aclHelperLink->expects($this->any())
            ->method('getService')
            ->willReturn($this->aclHelper);

        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturn(true);

        $this->associationManager = new AssociationManager(
            $this->configManager,
            $aclHelperLink,
            new DoctrineHelper($doctrine),
            $this->entityNameResolver,
            $featureChecker
        );
    }

    public function testGetSingleOwnerManyToOneAssociationTargets()
    {
        $associationOwnerClass = 'TestAssociationOwnerClass';
        $associationKind = 'TestAssociationKind';
        $scope = 'TestScope';
        $attribute = 'TestAttribute';

        $association1 = ExtendHelper::buildAssociationName('TargetClass1', $associationKind);
        $association2 = ExtendHelper::buildAssociationName('TargetClass2', $associationKind);
        $relations = [
            'manyToOne|TestAssociationOwnerClass|TargetClass1|' . $association1 => [
                'field_id'      => new FieldConfigId(
                    'extend',
                    $associationOwnerClass,
                    $association1,
                    RelationType::MANY_TO_ONE
                ),
                'owner'         => true,
                'target_entity' => 'TargetClass1'
            ],
            'manyToOne|TestAssociationOwnerClass|TargetClass2|' . $association2 => [
                'field_id'      => new FieldConfigId(
                    'extend',
                    $associationOwnerClass,
                    $association2,
                    RelationType::MANY_TO_ONE
                ),
                'owner'         => true,
                'target_entity' => 'TargetClass2'
            ],
            'manyToOne|TestAssociationOwnerClass|TargetClass1|relation1'        => [
                'field_id'      => new FieldConfigId(
                    'extend',
                    $associationOwnerClass,
                    'relation1',
                    RelationType::MANY_TO_ONE
                ),
                'owner'         => true,
                'target_entity' => 'TargetClass1'
            ]
        ];

        $ownerExtendConfig = new Config(new EntityConfigId('extend', $associationOwnerClass));
        $ownerExtendConfig->set('relation', $relations);

        $target1Config = new Config(new EntityConfigId($scope, 'TargetClass1'));
        $target1Config->set($attribute, true);
        $target2Config = new Config(new EntityConfigId($scope, 'TargetClass2'));

        $extendProvider = $this->createMock(ConfigProvider::class);
        $scopeProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap(
                [
                    ['extend', $extendProvider],
                    [$scope, $scopeProvider]
                ]
            );
        $extendProvider->expects($this->once())
            ->method('getConfig')
            ->with($associationOwnerClass)
            ->willReturn($ownerExtendConfig);
        $scopeProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap(
                [
                    ['TargetClass1', null, $target1Config],
                    ['TargetClass2', null, $target2Config]
                ]
            );

        $result = $this->associationManager->getAssociationTargets(
            $associationOwnerClass,
            $this->associationManager->getSingleOwnerFilter($scope, $attribute),
            RelationType::MANY_TO_ONE,
            $associationKind
        );

        $this->assertEquals(
            [
                'TargetClass1' => $association1
            ],
            $result
        );
    }

    public function testGetSingleOwnerMultipleManyToOneAssociationTargets()
    {
        $associationOwnerClass = 'TestAssociationOwnerClass';
        $associationKind = 'TestAssociationKind';
        $scope = 'TestScope';
        $attribute = 'TestAttribute';

        $association1 = ExtendHelper::buildAssociationName('TargetClass1', $associationKind);
        $association2 = ExtendHelper::buildAssociationName('TargetClass2', $associationKind);
        $relations = [
            'manyToOne|TestAssociationOwnerClass|TargetClass1|' . $association1 => [
                'field_id'      => new FieldConfigId(
                    'extend',
                    $associationOwnerClass,
                    $association1,
                    RelationType::MANY_TO_ONE
                ),
                'owner'         => true,
                'target_entity' => 'TargetClass1'
            ],
            'manyToOne|TestAssociationOwnerClass|TargetClass2|' . $association2 => [
                'field_id'      => new FieldConfigId(
                    'extend',
                    $associationOwnerClass,
                    $association2,
                    RelationType::MANY_TO_ONE
                ),
                'owner'         => true,
                'target_entity' => 'TargetClass2'
            ],
            'manyToOne|TestAssociationOwnerClass|TargetClass1|relation1'        => [
                'field_id'      => new FieldConfigId(
                    'extend',
                    $associationOwnerClass,
                    'relation1',
                    RelationType::MANY_TO_ONE
                ),
                'owner'         => true,
                'target_entity' => 'TargetClass1'
            ]
        ];

        $ownerExtendConfig = new Config(new EntityConfigId('extend', $associationOwnerClass));
        $ownerExtendConfig->set('relation', $relations);

        $target1Config = new Config(new EntityConfigId($scope, 'TargetClass1'));
        $target1Config->set($attribute, true);
        $target2Config = new Config(new EntityConfigId($scope, 'TargetClass2'));

        $extendProvider = $this->createMock(ConfigProvider::class);
        $scopeProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap(
                [
                    ['extend', $extendProvider],
                    [$scope, $scopeProvider]
                ]
            );
        $extendProvider->expects($this->once())
            ->method('getConfig')
            ->with($associationOwnerClass)
            ->willReturn($ownerExtendConfig);
        $scopeProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap(
                [
                    ['TargetClass1', null, $target1Config],
                    ['TargetClass2', null, $target2Config]
                ]
            );

        $result = $this->associationManager->getAssociationTargets(
            $associationOwnerClass,
            $this->associationManager->getSingleOwnerFilter($scope, $attribute),
            RelationType::MULTIPLE_MANY_TO_ONE,
            $associationKind
        );

        $this->assertEquals(
            [
                'TargetClass1' => $association1
            ],
            $result
        );
    }

    public function testGetMultiOwnerManyToManyAssociationTargets()
    {
        $associationOwnerClass = 'TestAssociationOwnerClass';
        $associationKind = 'TestAssociationKind';
        $scope = 'TestScope';
        $attribute = 'TestAttribute';

        $association1 = ExtendHelper::buildAssociationName('TargetClass1', $associationKind);
        $association2 = ExtendHelper::buildAssociationName('TargetClass2', $associationKind);
        $relations = [
            'manyToMany|TestAssociationOwnerClass|TargetClass1|' . $association1 => [
                'field_id'      => new FieldConfigId(
                    'extend',
                    $associationOwnerClass,
                    $association1,
                    RelationType::MANY_TO_MANY
                ),
                'owner'         => true,
                'target_entity' => 'TargetClass1'
            ],
            'manyToMany|TestAssociationOwnerClass|TargetClass2|' . $association2 => [
                'field_id'      => new FieldConfigId(
                    'extend',
                    $associationOwnerClass,
                    $association2,
                    RelationType::MANY_TO_MANY
                ),
                'owner'         => true,
                'target_entity' => 'TargetClass2'
            ],
            'manyToMany|TestAssociationOwnerClass|TargetClass1|relation1'        => [
                'field_id'      => new FieldConfigId(
                    'extend',
                    $associationOwnerClass,
                    'relation1',
                    RelationType::MANY_TO_MANY
                ),
                'owner'         => true,
                'target_entity' => 'TargetClass1'
            ]
        ];

        $ownerExtendConfig = new Config(new EntityConfigId('extend', $associationOwnerClass));
        $ownerExtendConfig->set('relation', $relations);

        $target1Config = new Config(new EntityConfigId($scope, 'TargetClass1'));
        $target1Config->set($attribute, [$associationOwnerClass, 'AnotherOwnerClass']);
        $target2Config = new Config(new EntityConfigId($scope, 'TargetClass2'));

        $extendProvider = $this->createMock(ConfigProvider::class);
        $scopeProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap(
                [
                    ['extend', $extendProvider],
                    [$scope, $scopeProvider]
                ]
            );
        $extendProvider->expects($this->once())
            ->method('getConfig')
            ->with($associationOwnerClass)
            ->willReturn($ownerExtendConfig);
        $scopeProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap(
                [
                    ['TargetClass1', null, $target1Config],
                    ['TargetClass2', null, $target2Config]
                ]
            );

        $result = $this->associationManager->getAssociationTargets(
            $associationOwnerClass,
            $this->associationManager->getMultiOwnerFilter($scope, $attribute),
            RelationType::MANY_TO_MANY,
            $associationKind
        );

        $this->assertEquals(
            [
                'TargetClass1' => $association1
            ],
            $result
        );
    }

    public function testGetMultiAssociationsQueryBuilder()
    {
        $ownerClass = TestOwner1::class;
        $targetClass1 = TestTarget1::class;
        $targetClass2 = TestTarget2::class;
        $filters = ['name' => 'test', 'phones.phone' => '123-456'];
        $joins = ['phones'];
        $associationTargets = [$targetClass1 => 'targets_1', $targetClass2 => 'targets_2'];

        $this->aclHelper->expects($this->exactly(2))
            ->method('apply')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (QueryBuilder $qb) {
                    return $qb->andWhere('target.age = 10')->getQuery();
                }),
                new ReturnCallback(function (QueryBuilder $qb) {
                    return $qb->andWhere('target.age = 100')->getQuery();
                })
            );

        $this->entityNameResolver->expects($this->exactly(2))
            ->method('getNameDQL')
            ->willReturnMap([
                [$targetClass1, 'target', null, null, 'CONCAT(target.firstName, CONCAT(\' \', target.lastName))'],
                [$targetClass2, 'target', null, null, 'CONCAT(target.firstName, CONCAT(\' \', target.lastName))']
            ]);
        $this->entityNameResolver->expects($this->any())
            ->method('prepareNameDQL')
            ->willReturnCallback(
                function ($expr, $castToString) {
                    self::assertTrue($castToString);

                    return $expr
                        ? sprintf('CAST(%s AS string)', $expr)
                        : '\'\'';
                }
            );

        $result = $this->associationManager->getMultiAssociationsQueryBuilder(
            $ownerClass,
            $filters,
            $joins,
            $associationTargets,
            5,
            2,
            'title'
        );

        $this->assertEquals(
            'SELECT entity.id_0 AS ownerId, entity.id_1 AS id, entity.sclr_2 AS entity, entity.sclr_3 AS title '
            . 'FROM ('
            . '(SELECT DISTINCT t0_.id AS id_0, t1_.id AS id_1, '
            . '\'' . $targetClass1 . '\' AS sclr_2, '
            . 'CAST(t1_.firstName || \' \' || t1_.lastName AS char) AS sclr_3 '
            . 'FROM test_owner1 t0_ '
            . 'INNER JOIN test_owner1_to_target1 t2_ ON t0_.id = t2_.owner_id '
            . 'INNER JOIN test_target1 t1_ ON t1_.id = t2_.target_id '
            . 'LEFT JOIN test_phone t3_ ON t0_.id = t3_.owner_id '
            . 'WHERE (t0_.name = \'test\' AND t3_.phone = \'123-456\') AND t1_.age = 10)'
            . ' UNION ALL '
            . '(SELECT DISTINCT t0_.id AS id_0, t1_.id AS id_1, '
            . '\'' . $targetClass2 . '\' AS sclr_2, '
            . 'CAST(t1_.firstName || \' \' || t1_.lastName AS char) AS sclr_3 '
            . 'FROM test_owner1 t0_ '
            . 'INNER JOIN test_owner1_to_target2 t2_ ON t0_.id = t2_.owner_id '
            . 'INNER JOIN test_target2 t1_ ON t1_.id = t2_.target_id '
            . 'LEFT JOIN test_phone t3_ ON t0_.id = t3_.owner_id '
            . 'WHERE (t0_.name = \'test\' AND t3_.phone = \'123-456\') AND t1_.age = 100)'
            . ') entity ORDER BY title ASC LIMIT 5 OFFSET 5',
            $result->getSQL()
        );
    }

    public function testGetMultiAssociationOwnersQueryBuilder()
    {
        $targetClass = TestTarget1::class;
        $ownerClass1 = TestOwner1::class;
        $ownerClass2 = TestOwner2::class;
        $filters = ['name' => 'test'];
        $joins = [];
        $associationOwners = [$ownerClass1 => 'targets_1', $ownerClass2 => 'targets_1'];

        $this->aclHelper->expects($this->exactly(2))
            ->method('apply')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (QueryBuilder $qb) {
                    return $qb->andWhere('target.age = 10')->getQuery();
                }),
                new ReturnCallback(function (QueryBuilder $qb) {
                    return $qb->andWhere('target.age = 100')->getQuery();
                })
            );

        $this->entityNameResolver->expects($this->exactly(2))
            ->method('getNameDQL')
            ->willReturnMap([
                [$ownerClass1, 'e', null, null, 'e.name'],
                [$ownerClass2, 'e', null, null, 'e.name']
            ]);
        $this->entityNameResolver->expects($this->any())
            ->method('prepareNameDQL')
            ->willReturnCallback(
                function ($expr, $castToString) {
                    self::assertTrue($castToString);

                    return $expr
                        ? sprintf('CAST(%s AS string)', $expr)
                        : '\'\'';
                }
            );

        $result = $this->associationManager->getMultiAssociationOwnersQueryBuilder(
            $targetClass,
            $filters,
            $joins,
            $associationOwners,
            5,
            2,
            'title'
        );

        $this->assertEquals(
            'SELECT entity.id_1 AS id, entity.sclr_2 AS entity, entity.sclr_3 AS title '
            . 'FROM ('
            . '(SELECT t0_.id AS id_0, t1_.id AS id_1, '
            . '\'' . $ownerClass1 . '\' AS sclr_2, '
            . 'CAST(t1_.name AS char) AS sclr_3 '
            . 'FROM test_owner1 t1_ '
            . 'INNER JOIN test_owner1_to_target1 t2_ ON t1_.id = t2_.owner_id '
            . 'INNER JOIN test_target1 t0_ ON t0_.id = t2_.target_id '
            . 'WHERE t1_.name = \'test\' AND t0_.age = 10)'
            . ' UNION ALL '
            . '(SELECT t0_.id AS id_0, t1_.id AS id_1, '
            . '\'' . $ownerClass2 . '\' AS sclr_2, '
            . 'CAST(t1_.name AS char) AS sclr_3 '
            . 'FROM test_owner2 t1_ '
            . 'INNER JOIN test_owner2_to_target1 t2_ ON t1_.id = t2_.owner_id '
            . 'INNER JOIN test_target1 t0_ ON t0_.id = t2_.target_id '
            . 'WHERE t1_.name = \'test\' AND t0_.age = 100)'
            . ') entity ORDER BY title ASC LIMIT 5 OFFSET 5',
            $result->getSQL()
        );
    }
}
