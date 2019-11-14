<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Type\AttributeMultiSelectType;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttributeMultiSelectTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var AttributeMultiSelectType */
    private $formType;

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $managerMock;

    protected function setUp()
    {
        $this->managerMock = $this->createMock(AttributeManager::class);

        $this->formType = new AttributeMultiSelectType($this->managerMock);
        $this->formType->setTranslator($this->getTranslator());
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    AttributeMultiSelectType::class => $this->formType
                ],
                []
            )
        ];
    }

    public function testSubmit()
    {
        $field = $this->getEntity(FieldConfigModel::class, ['id' => 777]);

        $this->managerMock->expects($this->once())
            ->method('getActiveAttributesByClass')
            ->with('some\class')
            ->willReturn([$field]);

        $this->managerMock->expects($this->once())
            ->method('getAttributeLabel')
            ->with($field)
            ->willReturn('Some label');

        $submittedData = [777];

        $form = $this->factory->create(AttributeMultiSelectType::class, [], ['attributeEntityClass' => 'some\class']);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $attributeGroup = new AttributeGroup();

        $attributeRelation = new AttributeGroupRelation();
        $attributeRelation->setEntityConfigFieldId(777);
        $attributeGroup->addAttributeRelation($attributeRelation);

        $formData = $form->getData();
        $this->assertEquals($attributeGroup->getAttributeRelations(), $formData);
    }

    public function testIsSystem()
    {
        $isSystem = true;
        $field = $this->getEntity(FieldConfigModel::class, ['id' => 777]);

        $this->managerMock->expects($this->once())
            ->method('getActiveAttributesByClass')
            ->with('')
            ->willReturn([$field]);

        $this->managerMock->expects($this->once())
            ->method('getAttributeLabel')
            ->with($field)
            ->willReturn('Some label');

        $this->managerMock->expects($this->once())
            ->method('isSystem')
            ->with($field)
            ->willReturn($isSystem);

        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $result = $resolver->resolve([]);

        $locked = call_user_func($result['choice_attr'], 777);
        $this->assertEquals(['locked' => 'locked'], $locked);
    }

    public function testGetParent()
    {
        $this->assertEquals(Select2ChoiceType::class, $this->formType->getParent());
    }

    /**
     * @dataProvider formChoicesDataProvider
     *
     * @param array $fields
     * @param array $fieldsData
     * @param array $expectedChoices
     */
    public function testFormChoices(array $fields, array $fieldsData, array $expectedChoices)
    {
        $this->managerMock->expects($this->atLeastOnce())
            ->method('getActiveAttributesByClass')
            ->with('')
            ->willReturn($fields);

        $this->managerMock->expects($this->atLeastOnce())
            ->method('getAttributeLabel')
            ->willReturnCallback(function (FieldConfigModel $field) use ($fieldsData) {
                return $fieldsData[$field->getId()]['label'];
            });

        $this->managerMock->method('isSystem')
            ->willReturnCallback(function (FieldConfigModel $field) use ($fieldsData) {
                return $fieldsData[$field->getId()]['isSystem'];
            });

        $builder = $this->factory->createBuilder(AttributeMultiSelectType::class, []);
        $form = $builder->getForm();
        $actualChoices = $form->getConfig()->getOption('choices');
        $this->assertEquals($expectedChoices, $actualChoices);
    }

    /**
     * @return array
     */
    public function formChoicesDataProvider(): array
    {
        /** @var FieldConfigModel $field1 */
        $field1 = $this->getEntity(FieldConfigModel::class, ['id' => 1, 'fieldName' => 'color_custom_1']);
        /** @var FieldConfigModel $field2 */
        $field2 = $this->getEntity(FieldConfigModel::class, ['id' => 2, 'fieldName' => 'size_custom']);
        /** @var FieldConfigModel $field3 */
        $field3 = $this->getEntity(FieldConfigModel::class, ['id' => 3, 'fieldName' => 'color']);
        /** @var FieldConfigModel $field4 */
        $field4 = $this->getEntity(FieldConfigModel::class, ['id' => 4, 'fieldName' => 'color_custom_2']);

        return [
            'unique labels' => [
                'fields' => [
                    $field1,
                    $field2,
                ],
                'fieldsData' => [
                    $field1->getId() => ['isSystem' => false, 'label' => 'Color'],
                    $field2->getId() => ['isSystem' => false, 'label' => 'Size'],
                ],
                'expectedChoices' => [
                    'Color' => $field1->getId(),
                    'Size' => $field2->getId(),
                ]
            ],
            'non unique labels' => [
                'fields' => [
                    $field1,
                    $field2,
                    $field3,
                    $field4,
                ],
                'fieldsData' => [
                    $field1->getId() => ['isSystem' => false, 'label' => 'Color'],
                    $field2->getId() => ['isSystem' => false, 'label' => 'Size'],
                    $field3->getId() => ['isSystem' => true, 'label' => 'Color'],
                    $field4->getId() => ['isSystem' => false, 'label' => 'Color'],
                ],
                'expectedChoices' => [
                    'Size' => $field2->getId(),
                    'Color(oro.entity_config.attribute.system)' => $field3->getId(),
                    'Color(color_custom_1)' => $field1->getId(),
                    'Color(color_custom_2)' => $field4->getId(),
                ]
            ],
        ];
    }
}
