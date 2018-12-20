<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\BuildCustomTypes;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;

class BuildCustomTypesTest extends \PHPUnit\Framework\TestCase
{
    /** @var CustomizeLoadedDataContext */
    private $context;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AssociationManager */
    private $associationManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var BuildCustomTypes */
    private $processor;

    protected function setUp()
    {
        $this->context = new CustomizeLoadedDataContext();
        $this->associationManager = $this->createMock(AssociationManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new BuildCustomTypes($this->associationManager, $this->doctrineHelper);
    }

    public function testProcessWhenNoData()
    {
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWithoutConfig()
    {
        $data = [
            'field1' => 123
        ];

        $this->context->setResult($data);
        $this->processor->process($this->context);
    }

    public function testProcessWithoutCustomFields()
    {
        $data = [
            'field1' => 123
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setDataType('integer');

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'field1' => 123
            ],
            $this->context->getResult()
        );
    }

    public function testProcessNestedObject()
    {
        $data = [
            'field1' => 'val1',
            'field2' => null,
            'field3' => 'val3'
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setExcluded();
        $config->addField('field2')->setExcluded();
        $config->addField('field3')->setExcluded();
        $nestedObjectFieldConfig = $config->addField('nestedObjectField');
        $nestedObjectFieldConfig->setDataType('nestedObject');
        $nestedObjectFieldTargetConfig = $nestedObjectFieldConfig->getOrCreateTargetEntity();
        $nestedObjectFieldTargetConfig->addField('targetField1')->setPropertyPath('field1');
        $nestedObjectFieldTargetConfig->addField('targetField2')->setPropertyPath('field2');
        $excludedTargetField = $nestedObjectFieldTargetConfig->addField('targetField3');
        $excludedTargetField->setPropertyPath('field3');
        $excludedTargetField->setExcluded();
        $nestedObjectFieldTargetConfig->addField('targetField4')->setPropertyPath('field4');

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'field1'            => 'val1',
                'field2'            => null,
                'field3'            => 'val3',
                'nestedObjectField' => [
                    'targetField1' => 'val1',
                    'targetField2' => null,
                    'targetField4' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessNestedObjectWithRenamedFields()
    {
        $data = [
            'renamedField1' => 'val1',
            'renamedField2' => null,
            'renamedField3' => 'val3'
        ];
        $config = new EntityDefinitionConfig();
        $field1 = $config->addField('renamedField1');
        $field1->setExcluded();
        $field1->setPropertyPath('field1');
        $field2 = $config->addField('renamedField2');
        $field2->setExcluded();
        $field2->setPropertyPath('field2');
        $field3 = $config->addField('renamedField3');
        $field3->setExcluded();
        $field3->setPropertyPath('field3');
        $nestedObjectFieldConfig = $config->addField('nestedObjectField');
        $nestedObjectFieldConfig->setDataType('nestedObject');
        $nestedObjectFieldTargetConfig = $nestedObjectFieldConfig->getOrCreateTargetEntity();
        $nestedObjectFieldTargetConfig->addField('targetField1')->setPropertyPath('field1');
        $nestedObjectFieldTargetConfig->addField('targetField2')->setPropertyPath('field2');
        $excludedTargetField = $nestedObjectFieldTargetConfig->addField('targetField3');
        $excludedTargetField->setPropertyPath('field3');
        $excludedTargetField->setExcluded();
        $nestedObjectFieldTargetConfig->addField('targetField4')->setPropertyPath('field4');

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'renamedField1'     => 'val1',
                'renamedField2'     => null,
                'renamedField3'     => 'val3',
                'nestedObjectField' => [
                    'targetField1' => 'val1',
                    'targetField2' => null,
                    'targetField4' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "field1.field11" property path is not supported.
     */
    public function testProcessNestedObjectWithNotSupportedPropertyPath()
    {
        $config = new EntityDefinitionConfig();
        $nestedObjectFieldConfig = $config->addField('nestedObjectField');
        $nestedObjectFieldConfig->setDataType('nestedObject');
        $nestedObjectFieldTargetConfig = $nestedObjectFieldConfig->getOrCreateTargetEntity();
        $nestedObjectFieldTargetConfig->addField('targetField1')->setPropertyPath('field1.field11');

        $this->context->setClassName('Test\Class');
        $this->context->setResult([]);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    public function testProcessNestedAssociation()
    {
        $data = [
            'targetEntityClass' => 'Test\TargetEntity',
            'targetEntityId'    => 123
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('targetEntityClass')->setExcluded();
        $config->addField('targetEntityId')->setExcluded();
        $nestedAssociationFieldConfig = $config->addField('nestedAssociationField');
        $nestedAssociationFieldConfig->setDataType('nestedAssociation');
        $nestedAssociationFieldTargetConfig = $nestedAssociationFieldConfig->getOrCreateTargetEntity();
        $classNameField = $nestedAssociationFieldTargetConfig->addField(ConfigUtil::CLASS_NAME);
        $classNameField->setPropertyPath('targetEntityClass');
        $classNameField->setMetaProperty(true);
        $nestedAssociationFieldTargetConfig->addField('id')->setPropertyPath('targetEntityId');

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'targetEntityClass'      => 'Test\TargetEntity',
                'targetEntityId'         => 123,
                'nestedAssociationField' => [
                    ConfigUtil::CLASS_NAME => 'Test\TargetEntity',
                    'id'                   => 123
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessNestedAssociationWithRenamedFields()
    {
        $data = [
            'renamedTargetEntityClass' => 'Test\TargetEntity',
            'renamedTargetEntityId'    => 123
        ];
        $config = new EntityDefinitionConfig();
        $targetEntityClassField = $config->addField('renamedTargetEntityClass');
        $targetEntityClassField->setExcluded();
        $targetEntityClassField->setPropertyPath('targetEntityClass');
        $targetEntityIdField = $config->addField('renamedTargetEntityId');
        $targetEntityIdField->setExcluded();
        $targetEntityIdField->setPropertyPath('targetEntityId');
        $nestedAssociationFieldConfig = $config->addField('nestedAssociationField');
        $nestedAssociationFieldConfig->setDataType('nestedAssociation');
        $nestedAssociationFieldTargetConfig = $nestedAssociationFieldConfig->getOrCreateTargetEntity();
        $classNameField = $nestedAssociationFieldTargetConfig->addField(ConfigUtil::CLASS_NAME);
        $classNameField->setPropertyPath('targetEntityClass');
        $classNameField->setMetaProperty(true);
        $nestedAssociationFieldTargetConfig->addField('id')->setPropertyPath('targetEntityId');

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'renamedTargetEntityClass' => 'Test\TargetEntity',
                'renamedTargetEntityId'    => 123,
                'nestedAssociationField'   => [
                    ConfigUtil::CLASS_NAME => 'Test\TargetEntity',
                    'id'                   => 123
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForExcludedExtendedAssociation()
    {
        $entityClass = 'Test\Class';
        $data = [
            'association1' => null,
            'association2' => ['id' => 2]
        ];
        $config = new EntityDefinitionConfig();
        $association = $config->addField('association');
        $association->setDataType('association:manyToOne:kind');
        $association->setExcluded();

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn($entityClass);
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($entityClass, null, 'manyToOne', 'kind')
            ->willReturn(
                ['Test\Target1' => 'association1', 'Test\Target2' => 'association2']
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'association1' => null,
                'association2' => ['id' => 2],
                'association'  => [
                    '__class__' => 'Test\Target2',
                    'id'        => 2
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForExtendedAssociation()
    {
        $entityClass = 'Test\Class';
        $data = [
            'association1' => null,
            'association2' => ['id' => 2]
        ];
        $config = new EntityDefinitionConfig();
        $association = $config->addField('association');
        $association->setDataType('association:manyToOne:kind');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn($entityClass);
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($entityClass, null, 'manyToOne', 'kind')
            ->willReturn(
                ['Test\Target1' => 'association1', 'Test\Target2' => 'association2']
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'association1' => null,
                'association2' => ['id' => 2],
                'association'  => [
                    '__class__' => 'Test\Target2',
                    'id'        => 2
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenExtendedAssociationValueIsAlreadySet()
    {
        $data = [
            'association'  => ['__class' => 'Test\Target1', 'id' => 1],
            'association1' => ['id' => 1]
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:manyToOne:kind');

        $this->associationManager->expects(self::never())
            ->method('getAssociationTargets');

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'association'  => ['__class' => 'Test\Target1', 'id' => 1],
                'association1' => ['id' => 1]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unsupported type of extended association: unknown.
     */
    public function testProcessForUnsupportedExtendedAssociation()
    {
        $data = [
            'association1' => null,
            'association2' => ['id' => 2]
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:unknown:kind');

        $this->associationManager->expects(self::never())
            ->method('getAssociationTargets');

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    public function testProcessForExtendedAssociationWhenModelIsInheritedFromEntity()
    {
        $data = [
            'association1' => null,
            'association2' => ['id' => 2]
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:manyToOne:kind');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with(UserProfile::class)
            ->willReturn(User::class);
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with(User::class, null, 'manyToOne', 'kind')
            ->willReturn(
                ['Test\Target1' => 'association1', 'Test\Target2' => 'association2']
            );

        $this->context->setClassName(UserProfile::class);
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'association1' => null,
                'association2' => ['id' => 2],
                'association'  => [
                    '__class__' => 'Test\Target2',
                    'id'        => 2
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForManyToOneExtendedAssociation()
    {
        $entityClass = 'Test\Class';
        $data = [
            'association1' => null,
            'association2' => ['id' => 2]
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:manyToOne:kind');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn($entityClass);
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($entityClass, null, 'manyToOne', 'kind')
            ->willReturn(
                ['Test\Target1' => 'association1', 'Test\Target2' => 'association2']
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'association1' => null,
                'association2' => ['id' => 2],
                'association'  => [
                    '__class__' => 'Test\Target2',
                    'id'        => 2
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForManyToOneExtendedAssociationWhenAllDependedAssociationsAreNull()
    {
        $entityClass = 'Test\Class';
        $data = [
            'association1' => null,
            'association2' => null
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:manyToOne:kind');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn($entityClass);
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($entityClass, null, 'manyToOne', 'kind')
            ->willReturn(
                ['Test\Target1' => 'association1', 'Test\Target2' => 'association2']
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'association1' => null,
                'association2' => null,
                'association'  => null
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForManyToManyExtendedAssociation()
    {
        $entityClass = 'Test\Class';
        $data = [
            'association1' => [],
            'association2' => [['id' => 2]],
            'association3' => [['id' => 3]]
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:manyToMany:kind');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn($entityClass);
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($entityClass, null, 'manyToMany', 'kind')
            ->willReturn(
                [
                    'Test\Target1' => 'association1',
                    'Test\Target2' => 'association2',
                    'Test\Target3' => 'association3'
                ]
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'association1' => [],
                'association2' => [['id' => 2]],
                'association3' => [['id' => 3]],
                'association'  => [
                    ['__class__' => 'Test\Target2', 'id' => 2],
                    ['__class__' => 'Test\Target3', 'id' => 3]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForManyToManyExtendedAssociationWhenAllDependedAssociationsAreEmpty()
    {
        $entityClass = 'Test\Class';
        $data = [
            'association1' => [],
            'association2' => []
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:manyToMany:kind');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn($entityClass);
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($entityClass, null, 'manyToMany', 'kind')
            ->willReturn(
                [
                    'Test\Target1' => 'association1',
                    'Test\Target2' => 'association2'
                ]
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'association1' => [],
                'association2' => [],
                'association'  => []
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForMultipleManyToOneExtendedAssociation()
    {
        $entityClass = 'Test\Class';
        $data = [
            'association1' => null,
            'association2' => ['id' => 2],
            'association3' => ['id' => 3]
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:multipleManyToOne:kind');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn($entityClass);
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($entityClass, null, 'multipleManyToOne', 'kind')
            ->willReturn(
                [
                    'Test\Target1' => 'association1',
                    'Test\Target2' => 'association2',
                    'Test\Target3' => 'association3'
                ]
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'association1' => null,
                'association2' => ['id' => 2],
                'association3' => ['id' => 3],
                'association'  => [
                    ['__class__' => 'Test\Target2', 'id' => 2],
                    ['__class__' => 'Test\Target3', 'id' => 3]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForMultipleManyToOneExtendedAssociationWhenAllDependedAssociationsAreNull()
    {
        $entityClass = 'Test\Class';
        $data = [
            'association1' => null,
            'association2' => null
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:multipleManyToOne:kind');

        $this->doctrineHelper->expects(self::once())
            ->method('resolveManageableEntityClass')
            ->with($entityClass)
            ->willReturn($entityClass);
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($entityClass, null, 'multipleManyToOne', 'kind')
            ->willReturn(
                [
                    'Test\Target1' => 'association1',
                    'Test\Target2' => 'association2'
                ]
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'association1' => null,
                'association2' => null,
                'association'  => []
            ],
            $this->context->getResult()
        );
    }
}
