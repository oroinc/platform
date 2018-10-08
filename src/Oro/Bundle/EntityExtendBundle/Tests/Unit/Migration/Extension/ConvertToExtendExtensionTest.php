<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsParser;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ConvertToExtendExtension;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ConvertToExtendExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityMetadataHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityMetadataHelper;

    /** @var ExtendOptionsManager */
    protected $extendOptionsManager;

    /** @var ExtendOptionsParser */
    protected $extendOptionsParser;

    /** @var ConfigModelManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configModelManager;

    /** @var QueryBag|\PHPUnit\Framework\MockObject\MockObject */
    protected $queries;

    /** @var  Schema|\PHPUnit\Framework\MockObject\MockObject */
    protected $schema;

    protected function setUp()
    {
        $this->entityMetadataHelper =
            $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper')
                ->disableOriginalConstructor()
                ->getMock();

        $this->extendOptionsManager = new ExtendOptionsManager();

        $this->configModelManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->queries =$this->getMockBuilder('Oro\Bundle\MigrationBundle\Migration\QueryBag')
            ->disableOriginalConstructor()
            ->getMock();

        $this->schema = $this->getMockBuilder('Doctrine\DBAL\Schema\Schema')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ConvertToExtendExtension
     */
    protected function getConvertToExtendExtension()
    {
        $result = new ConvertToExtendExtension(
            $this->extendOptionsManager,
            $this->entityMetadataHelper,
            $this->configModelManager
        );

        return $result;
    }

    public function testManyToOneRelationNewFiledNameSameCurrentFieldName()
    {
        $convertToExtendExtension = $this->getConvertToExtendExtension();

        $configFieldModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->disableOriginalConstructor()->getMock();

        $configFieldModel->expects($this->any())
            ->method('toArray')
            ->will(
                $this->returnValueMap(
                    [
                        ['entity', ['label' => 'label', 'description' => 'description']],
                        ['extend', ['is_extend' => false]],
                        ['form', ['is_enabled' => false]],
                        ['view', ['is_displayable' => true]],
                        ['merge', ['display' => true]],
                        ['dataaudit', ['auditable' => true]]
                    ]
                )
            );

        $this->configModelManager->expects(self::once())->method('getFieldModel')
            ->willReturn($configFieldModel);

        $currentEntityName = 'TestEntityClass';
        $currentAssociationName = 'TestFieldClass';
        $table = 'testTable';
        $associationName = 'TestFieldClass';
        $targetTable = 'TargetTable';
        $targetColumnName = 'id';
        $options = [
            'entity' => ['label' => 'label2', 'description' => 'description2'],
            'form' => ['is_enabled' => true, 'form_type' => 'oro_channel_select_type2'],
            'view' => ['is_displayable' => false],
            'merge' => ['display' => false],
            'dataaudit' => ['auditable' => false]
        ];

        $tableObject = $this->getMockBuilder('Doctrine\DBAL\Schema\Table')->disableOriginalConstructor()->getMock();


        $this->schema->expects(self::once())->method('getTable')->willReturn($tableObject);
        $this->queries->expects(self::never())->method('addQuery');

        $convertToExtendExtension->manyToOneRelation(
            $this->queries,
            $this->schema,
            $currentEntityName,
            $currentAssociationName,
            $table,
            $associationName,
            $targetTable,
            $targetColumnName,
            $options
        );

        $expectedOptions = [
            'testTable!TestFieldClass' => [
                'extend' => [
                    'is_extend' => true,
                    'owner' => 'Custom'
                ],
                '_target' => [
                    'table_name' => 'TargetTable',
                    'column' => 'id'
                ],
                'entity' => [
                    'label' => 'label2',
                    'description' => 'description2'
                ],
                '_type' => 'manyToOne',
                 'form' => ['is_enabled' => true, 'form_type' => 'oro_channel_select_type2'],
                'view' => ['is_displayable' => false],
                'merge' => ['display' => false],
                'dataaudit' => ['auditable' => false]
            ]
        ];

        self::assertEquals($expectedOptions, $this->extendOptionsManager->getExtendOptions());
    }

    public function testManyToOneRelationNewFiledNameNotSameCurrentFieldName()
    {
        $convertToExtendExtension = $this->getConvertToExtendExtension();

        $configFieldModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->disableOriginalConstructor()->getMock();

        $this->configModelManager->expects(self::once())->method('getFieldModel')
            ->willReturn($configFieldModel);

        $configFieldModel->expects($this->any())
            ->method('toArray')
            ->will(
                $this->returnValueMap(
                    [
                        ['entity', ['label' => 'label', 'description' => 'description']],
                        ['extend', ['is_extend' => false]],
                        ['form', ['is_enabled' => false]],
                        ['view', ['is_displayable' => true]],
                        ['merge', ['display' => true]],
                        ['dataaudit', ['auditable' => true]]
                    ]
                )
            );

        $currentEntityName = 'TestEntityClass';
        $currentAssociationName = 'TestFieldClass';
        $table = 'testTable';
        $associationName = 'NewTestFieldClass';
        $targetTable = 'TargetTable';
        $targetColumnName = 'id';
        $options = [
            'entity' => ['label' => 'label2', 'description' => 'description2'],
            'form' => ['is_enabled' => true, 'form_type' => 'oro_channel_select_type2'],
            'view' => ['is_displayable' => false],
            'merge' => ['display' => false],
            'dataaudit' => ['auditable' => false]
        ];

        $tableObject = $this->getMockBuilder('Doctrine\DBAL\Schema\Table')->disableOriginalConstructor()->getMock();

        $this->schema->expects(self::once())->method('getTable')->willReturn($tableObject);
        $this->queries->expects(self::once())->method('addQuery');

        $convertToExtendExtension->manyToOneRelation(
            $this->queries,
            $this->schema,
            $currentEntityName,
            $currentAssociationName,
            $table,
            $associationName,
            $targetTable,
            $targetColumnName,
            $options
        );

        $expectedOptions = [
            'testTable!NewTestFieldClass' => [
                'extend' => [
                    'is_extend' => true,
                    'owner' => 'Custom'
                ],
                '_target' => [
                    'table_name' => 'TargetTable',
                    'column' => 'id'
                ],
                'entity' => [
                    'label' => 'label2',
                    'description' => 'description2'
                ],
                '_type' => 'manyToOne',
                'form' => ['is_enabled' => true, 'form_type' => 'oro_channel_select_type2'],
                'view' => ['is_displayable' => false],
                'merge' => ['display' => false],
                'dataaudit' => ['auditable' => false]
            ]
        ];

        self::assertEquals($expectedOptions, $this->extendOptionsManager->getExtendOptions());
    }
}
