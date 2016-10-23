<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\BuildNestedObjects;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;

class BuildNestedObjectsTest extends \PHPUnit_Framework_TestCase
{
    /** @var CustomizeLoadedDataContext */
    protected $context;

    /** @var BuildNestedObjects */
    protected $processor;

    protected function setUp()
    {
        $this->context = new CustomizeLoadedDataContext();
        $this->processor = new BuildNestedObjects();
    }

    public function testProcessWhenNoData()
    {
        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasResult());
    }

    public function testProcessWithoutNestedFields()
    {
        $data = [
            'field1' => 123
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setDataType('integer');

        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            [
                'field1' => 123
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForNestedField()
    {
        $data = [
            'field1' => 'val1',
            'field2' => null,
            'field3' => 'val3',
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setExcluded();
        $config->addField('field2')->setExcluded();
        $config->addField('field3')->setExcluded();
        $nestedFieldConfig = $config->addField('nestedField');
        $nestedFieldConfig->setDataType('nestedObject');
        $nestedFieldTargetConfig = $nestedFieldConfig->getOrCreateTargetEntity();
        $nestedFieldTargetConfig->addField('targetField1')->setPropertyPath('field1');
        $nestedFieldTargetConfig->addField('targetField2')->setPropertyPath('field2');
        $excludedTargetField = $nestedFieldTargetConfig->addField('targetField3');
        $excludedTargetField->setPropertyPath('field3');
        $excludedTargetField->setExcluded();
        $nestedFieldTargetConfig->addField('targetField4')->setPropertyPath('field4');

        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            [
                'field1'      => 'val1',
                'field2'      => null,
                'field3'      => 'val3',
                'nestedField' => [
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
     * @expectedExceptionMessage The "field1.field11" property path is not supported for the nested object.
     */
    public function testProcessForNestedFieldWithNotSupportedPropertyPath()
    {
        $config = new EntityDefinitionConfig();
        $nestedFieldConfig = $config->addField('nestedField');
        $nestedFieldConfig->setDataType('nestedObject');
        $nestedFieldTargetConfig = $nestedFieldConfig->getOrCreateTargetEntity();
        $nestedFieldTargetConfig->addField('targetField1')->setPropertyPath('field1.field11');

        $this->context->setResult([]);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }
}
