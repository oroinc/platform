<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Entity\Manager;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class AssociationManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var AssociationManager */
    private $associationManager;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->associationManager = new AssociationManager($this->configManager);
    }

    public function testGetSingleOwnerManyToOneAssociationTargets()
    {
        $associationOwnerClass = 'TestAssociationOwnerClass';
        $associationKind       = 'TestAssociationKind';
        $scope                 = 'TestScope';
        $attribute             = 'TestAttribute';

        $association1 = ExtendHelper::buildAssociationName('TargetClass1', $associationKind);
        $association2 = ExtendHelper::buildAssociationName('TargetClass2', $associationKind);
        $relations    = [
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

        $extendProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $scopeProvider  = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
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
        $associationKind       = 'TestAssociationKind';
        $scope                 = 'TestScope';
        $attribute             = 'TestAttribute';

        $association1 = ExtendHelper::buildAssociationName('TargetClass1', $associationKind);
        $association2 = ExtendHelper::buildAssociationName('TargetClass2', $associationKind);
        $relations    = [
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

        $extendProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $scopeProvider  = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
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
        $associationKind       = 'TestAssociationKind';
        $scope                 = 'TestScope';
        $attribute             = 'TestAttribute';

        $association1 = ExtendHelper::buildAssociationName('TargetClass1', $associationKind);
        $association2 = ExtendHelper::buildAssociationName('TargetClass2', $associationKind);
        $relations    = [
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

        $extendProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $scopeProvider  = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
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
}
