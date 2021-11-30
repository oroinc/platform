<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\EntityConfig\Mock\ConfigurationHandlerMock;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ConvertToExtendExtension;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ConvertToExtendExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityMetadataHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityMetadataHelper;

    /** @var ExtendOptionsManager */
    private $extendOptionsManager;

    /** @var ConfigModelManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configModelManager;

    /** @var QueryBag|\PHPUnit\Framework\MockObject\MockObject */
    private $queries;

    /** @var Schema|\PHPUnit\Framework\MockObject\MockObject */
    private $schema;

    /** @var ConvertToExtendExtension */
    private $convertToExtendExtension;

    protected function setUp(): void
    {
        $this->entityMetadataHelper = $this->createMock(EntityMetadataHelper::class);
        $this->extendOptionsManager = new ExtendOptionsManager(ConfigurationHandlerMock::getInstance());
        $this->configModelManager = $this->createMock(ConfigModelManager::class);
        $this->queries = $this->createMock(QueryBag::class);
        $this->schema = $this->createMock(Schema::class);

        $this->convertToExtendExtension = new ConvertToExtendExtension(
            $this->extendOptionsManager,
            $this->entityMetadataHelper,
            $this->configModelManager
        );
    }

    public function testManyToOneRelationNewFiledNameSameCurrentFieldName()
    {
        $configFieldModel = $this->createMock(FieldConfigModel::class);

        $configFieldModel->expects($this->any())
            ->method('toArray')
            ->willReturnMap([
                ['entity', ['label' => 'label', 'description' => 'description']],
                ['extend', ['is_extend' => false]],
                ['form', ['is_enabled' => false]],
                ['view', ['is_displayable' => true]],
                ['merge', ['display' => true]],
                ['dataaudit', ['auditable' => true]]
            ]);

        $this->configModelManager->expects(self::once())
            ->method('getFieldModel')
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

        $tableObject = $this->createMock(Table::class);

        $this->schema->expects(self::once())
            ->method('getTable')
            ->willReturn($tableObject);
        $this->queries->expects(self::never())
            ->method('addQuery');

        $this->convertToExtendExtension->manyToOneRelation(
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
        $configFieldModel = $this->createMock(FieldConfigModel::class);

        $this->configModelManager->expects(self::once())
            ->method('getFieldModel')
            ->willReturn($configFieldModel);

        $configFieldModel->expects($this->any())
            ->method('toArray')
            ->willReturnMap(
                [
                    ['entity', ['label' => 'label', 'description' => 'description']],
                    ['extend', ['is_extend' => false]],
                    ['form', ['is_enabled' => false]],
                    ['view', ['is_displayable' => true]],
                    ['merge', ['display' => true]],
                    ['dataaudit', ['auditable' => true]]
                ]
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

        $tableObject = $this->createMock(Table::class);

        $this->schema->expects(self::once())
            ->method('getTable')
            ->willReturn($tableObject);
        $this->queries->expects(self::once())
            ->method('addQuery');

        $this->convertToExtendExtension->manyToOneRelation(
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
