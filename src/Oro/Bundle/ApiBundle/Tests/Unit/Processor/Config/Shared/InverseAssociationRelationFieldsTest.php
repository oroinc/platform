<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\InverseAssociationRelationFields;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

class InverseAssociationRelationFieldsTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var InverseAssociationRelationFields */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new InverseAssociationRelationFields(
            $this->extendConfigProvider,
            $this->doctrineHelper,
            $this->valueNormalizer
        );
    }

    public function testProcessWithoutAssociationClass()
    {
        $this->processor->process($this->context);
        $this->assertNull($this->context->getResult());
    }

    public function testProcessWithNonManageableClass()
    {
        $this->processor->setAssociationClass('Test\Class');
        $this->context->setClassName('Non\Manageable\Class');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with('Non\Manageable\Class')
            ->willReturn(false);

        $this->processor->process($this->context);
        $this->assertNull($this->context->getResult());
    }

    public function testProcessWithNonConfigurableSourceClass()
    {
        $this->processor->setAssociationClass('Test\Class');
        $this->context->setClassName('Target\Class');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with('Target\Class')
            ->willReturn(true);

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Class')
            ->willReturn(false);

        $this->processor->process($this->context);
        $this->assertNull($this->context->getResult());
    }

    public function testProcessWithoutRelations()
    {
        $this->processor->setAssociationClass('Test\Class');

        $this->context->setClassName('Target\Class');
        $config = new Config(new EntityConfigId('extend', 'Target\Class'));

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with('Target\Class')
            ->willReturn(true);

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Class')
            ->willReturn(true);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Class')
            ->willReturn($config);

        $this->processor->process($this->context);
        $this->assertNull($this->context->getResult());
    }

    public function testProcess()
    {
        $this->processor->setAssociationClass('Test\Class');
        $this->context->setClassName('Target\Class');
        $config = new Config(new EntityConfigId('extend', 'Test\Class'));

        $relations = [
            [
                'field_id' => new FieldConfigId('extend', 'Test\Class', 'field1', RelationType::ONE_TO_MANY),
                'target_entity' => 'Target\Class',
            ],
            [
                'field_id' => new FieldConfigId('extend', 'Test\Class', 'field2', RelationType::MANY_TO_ONE),
                'target_entity' => 'Target\Class',
            ],
            [
                'field_id' => new FieldConfigId('extend', 'Test\Class', 'class_721b3cd', RelationType::MANY_TO_ONE),
                'target_entity' => 'Another\Class',
            ],
            [
                'field_id' => new FieldConfigId('extend', 'Test\Class', 'class_e8404c1', RelationType::MANY_TO_ONE),
                'target_entity' => 'Target\Class',
            ],
        ];
        $config->set('relation', $relations);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject([]);
        $this->context->setResult($configObject);

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with('Target\Class')
            ->willReturn(true);

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Class')
            ->willReturn(true);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Class')
            ->willReturn($config);

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->willReturn('test_association_fields');

        $this->processor->process($this->context);

        $fields = $this->context->getResult()->getFields();
        $this->assertCount(1, $fields);
        /** @var EntityDefinitionFieldConfig $fieldConfig */
        $fieldConfig = $fields['test_association_fields'];
        $this->assertEquals('inverseAssociation:Test\Class:manyToOne', $fieldConfig->getDataType());
    }
}
