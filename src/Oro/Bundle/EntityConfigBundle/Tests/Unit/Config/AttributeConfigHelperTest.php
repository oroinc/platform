<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Oro\Bundle\EntityConfigBundle\Config\AttributeConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class AttributeConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $attributeConfigProvider;

    /**
     * @var AttributeConfigHelper
     */
    private $helper;

    protected function setUp()
    {
        $this->attributeConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new AttributeConfigHelper($this->attributeConfigProvider);
    }

    /**
     * @return array
     */
    public function isFieldAttributeDataProvider()
    {
        return [
            'has config and is attribute' => [
                'hasConfig' => true,
                'isAttribute' => true,
                'willReturn' => true
            ],
            'has config and is not attribute' => [
                'hasConfig' => true,
                'isAttribute' => false,
                'willReturn' => false
            ],
            'has no config' => [
                'hasConfig' => false,
                'isAttribute' => null,
                'willReturn' => false
            ],
        ];
    }

    /**
     * @dataProvider isFieldAttributeDataProvider
     *
     * @param bool $hasConfig
     * @param bool $isAttribute
     * @param bool $willReturn
     */
    public function testIsFieldAttribute($hasConfig, $isAttribute, $willReturn)
    {
        $entityClass = 'EntityClass';
        $fieldName = 'fieldName';

        $config = $this->createMock(ConfigInterface::class);
        $this->attributeConfigProvider
            ->expects($this->exactly((int)$hasConfig))
            ->method('getConfig')
            ->with($entityClass, $fieldName)
            ->willReturn($config);

        $this->attributeConfigProvider
            ->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass, $fieldName)
            ->willReturn($hasConfig);

        $config
            ->expects($this->exactly((int)$hasConfig))
            ->method('is')
            ->with('is_attribute')
            ->willReturn($isAttribute);

        $this->assertEquals($willReturn, $this->helper->isFieldAttribute($entityClass, $fieldName));
    }

    /**
     * @return array
     */
    public function isEntityWithAttributesDataProvider()
    {
        return [
            'has config and has attributes' => [
                'hasConfig' => true,
                'isWithAttributes' => true,
                'willReturn' => true
            ],
            'has config and has not attributes' => [
                'hasConfig' => true,
                'isWithAttributes' => false,
                'willReturn' => false
            ],
            'has no config' => [
                'hasConfig' => false,
                'isWithAttributes' => null,
                'willReturn' => false
            ],
        ];
    }

    /**
     * @dataProvider isEntityWithAttributesDataProvider
     *
     * @param bool $hasConfig
     * @param bool $isWithAttributes
     * @param bool $willReturn
     */
    public function testIsEntityWithAttributes($hasConfig, $isWithAttributes, $willReturn)
    {
        $entityClass = 'EntityClass';

        $config = $this->createMock(ConfigInterface::class);
        $this->attributeConfigProvider
            ->expects($this->exactly((int)$hasConfig))
            ->method('getConfig')
            ->with($entityClass)
            ->willReturn($config);

        $this->attributeConfigProvider
            ->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn($hasConfig);

        $config
            ->expects($this->exactly((int)$hasConfig))
            ->method('is')
            ->with('has_attributes')
            ->willReturn($isWithAttributes);

        $this->assertEquals($willReturn, $this->helper->isEntityWithAttributes($entityClass));
    }
}
