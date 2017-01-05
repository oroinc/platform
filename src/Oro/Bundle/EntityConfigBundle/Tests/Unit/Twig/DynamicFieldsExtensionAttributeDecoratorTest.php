<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityConfigBundle\Config\AttributeConfigHelper;
use Oro\Bundle\EntityConfigBundle\Twig\DynamicFieldsExtensionAttributeDecorator;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Twig\AbstractDynamicFieldsExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Component\Testing\Unit\EntityTrait;

class DynamicFieldsExtensionAttributeDecoratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const ENTITY_CLASS_NAME = 'entity_class';

    /**
     * @var AbstractDynamicFieldsExtension|\PHPUnit_Framework_MockObject_MockObject
     */
    private $extension;

    /**
     * @var AttributeConfigHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeConfigHelper;

    /**
     * @var DynamicFieldsExtensionAttributeDecorator
     */
    private $decorator;

    protected function setUp()
    {
        $this->extension = $this->getMockBuilder(AbstractDynamicFieldsExtension::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeConfigHelper = $this->getMockBuilder(AttributeConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->decorator = new DynamicFieldsExtensionAttributeDecorator(
            $this->extension,
            $this->attributeConfigHelper
        );
    }

    public function testGetName()
    {
        $this->assertEquals(AbstractDynamicFieldsExtension::NAME, $this->decorator->getName());
    }

    public function testGetFunctions()
    {
        $functions = $this->decorator->getFunctions();
        $this->assertCount(2, $functions);
        $this->assertInstanceOf(\Twig_SimpleFunction::class, $functions[0]);
        $this->assertInstanceOf(\Twig_SimpleFunction::class, $functions[1]);
    }

    public function testGetField()
    {
        $expectedData = [
            'type' => 'bigint',
            'label' => 'SomeLabel',
            'value' => 777
        ];
        $this->extension
            ->expects($this->once())
            ->method('getField')
            ->willReturn($expectedData);

        $entity = $this->getEntity(TestActivityTarget::class);
        /** @var FieldConfigModel $field */
        $field = $this->getEntity(FieldConfigModel::class);
        $this->assertEquals($expectedData, $this->decorator->getField($entity, $field));
    }

    /**
     * @return array
     */
    public function getFieldsDataProvider()
    {
        return [
            'no attributes' => [
                'fields' => [
                    'extendField1' => [],
                    'extendField2' => [],
                ],
                'entityClass' => self::ENTITY_CLASS_NAME,
                'attributeHelperWiths' => [
                    [self::ENTITY_CLASS_NAME, 'extendField1'],
                    [self::ENTITY_CLASS_NAME, 'extendField2'],
                ],
                'attributeHelperReturns' => [
                    false,
                    false
                ],
                'expectedFields' => [
                    'extendField1' => [],
                    'extendField2' => [],
                ]
            ],
            'attributes and extend fields' => [
                'fields' => [
                    'extendField1' => [],
                    'attribute1' => [],
                    'extendField2' => [],
                    'attribute2' => [],
                ],
                'entityClass' => self::ENTITY_CLASS_NAME,
                'attributeHelperWiths' => [
                    [self::ENTITY_CLASS_NAME, 'extendField1'],
                    [self::ENTITY_CLASS_NAME, 'attribute1'],
                    [self::ENTITY_CLASS_NAME, 'extendField2'],
                    [self::ENTITY_CLASS_NAME, 'attribute2'],
                ],
                'attributeHelperReturns' => [
                    false,
                    true,
                    false,
                    true
                ],
                'expectedFields' => [
                    'extendField1' => [],
                    'extendField2' => [],
                ]
            ],
            'attributes only' => [
                'fields' => [
                    'attribute1' => [],
                    'attribute2' => [],
                ],
                'entityClass' => null,
                'attributeHelperWiths' => [
                    [TestActivityTarget::class, 'attribute1'],
                    [TestActivityTarget::class, 'attribute2'],
                ],
                'attributeHelperReturns' => [
                    true,
                    true
                ],
                'expectedFields' => []
            ],
        ];
    }

    /**
     * @dataProvider getFieldsDataProvider
     *
     * @param array $fields
     * @param string $entityClass
     * @param array $attributeHelperWiths
     * @param array $attributeHelperReturns
     * @param array $expectedFields
     */
    public function testGetFields(
        array $fields,
        $entityClass,
        array $attributeHelperWiths,
        array $attributeHelperReturns,
        array $expectedFields
    ) {
        $entity = $this->getEntity(TestActivityTarget::class);

        $this->extension
            ->expects($this->once())
            ->method('getFields')
            ->with($entity, $entityClass)
            ->willReturn($fields);

        $this->attributeConfigHelper
            ->expects($this->exactly(count($fields)))
            ->method('isFieldAttribute')
            ->withConsecutive(...$attributeHelperWiths)
            ->willReturnOnConsecutiveCalls(...$attributeHelperReturns);

        $this->assertEquals($expectedFields, $this->decorator->getFields($entity, $entityClass));
    }
}
