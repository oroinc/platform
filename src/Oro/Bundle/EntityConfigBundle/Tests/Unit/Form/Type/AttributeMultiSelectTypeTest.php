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
        $this->managerMock = $this->getMockBuilder(AttributeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new AttributeMultiSelectType($this->managerMock);
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
}
