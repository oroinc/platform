<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Form\Type\AttributeGroupCollectionType;
use Oro\Bundle\EntityConfigBundle\Form\Type\AttributeGroupType;
use Oro\Bundle\EntityConfigBundle\Form\Type\AttributeMultiSelectType;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\Select2Type;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

class AttributeGroupCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var AttributeGroupCollectionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->formType = new AttributeGroupCollectionType();
    }

    /**
     * @return array
     */
    public function getExtensions()
    {
        $attributeManagerMock = $this->getMockBuilder(AttributeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $attributeManagerMock->expects($this->any())->method('getActiveAttributesByClass')->willReturn([]);

        return [
            new PreloadedExtension([
                CollectionType::NAME => new CollectionType(),
                AttributeGroupType::NAME => new AttributeGroupType(),
                LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionTypeStub(),
                AttributeMultiSelectType::NAME => new AttributeMultiSelectType($attributeManagerMock),
                'oro_select2_choice' => new Select2Type(
                    'Symfony\Component\Form\Extension\Core\Type\ChoiceType',
                    'oro_select2_choice'
                ),
            ], []),

        ];
    }

    /**
     * @param string|null $string
     * @param string|null $text
     * @return LocalizedFallbackValue
     */
    protected function createLocalizedValue($string = null, $text = null)
    {
        $value = new LocalizedFallbackValue();
        $value->setString($string)->setText($text);

        return $value;
    }

    public function testSubmit()
    {
        $existingEntity = new AttributeGroup();
        $existingEntity->addLabel($this->createLocalizedValue('Group1 Label 1'));
        $options = ['options' =>
            [
                'attributeEntityClass' => 'EnityClass',
                'data_class' => AttributeGroup::class
            ]
        ];
        $form = $this->factory->create($this->formType, [$existingEntity], $options);

        $submittedData = [
            [
                'labels' => [
                    ['string' => 'Group1 Label 1'],
                    ['string' => 'Group1 Label 2'],
                ],
            ],
            [
                'labels' => [
                    ['string' => 'Group2 Label 3'],
                    ['string' => 'Group2 Label 4'],
                ],
                'isVisible' => 1
            ],
        ];

        $form->submit($submittedData, [$existingEntity]);
        $this->assertTrue($form->isValid());

        $existingEntity->addLabel($this->createLocalizedValue('Group1 Label 2'));
        $entity = new AttributeGroup();
        $entity->addLabel($this->createLocalizedValue('Group2 Label 3'));
        $entity->addLabel($this->createLocalizedValue('Group2 Label 4'));

        $formData = $form->getData();
        $this->assertEquals([$existingEntity, $entity], $formData);
    }

    public function testGetName()
    {
        $this->assertSame(AttributeGroupCollectionType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertSame(CollectionType::NAME, $this->formType->getParent());
    }
}
