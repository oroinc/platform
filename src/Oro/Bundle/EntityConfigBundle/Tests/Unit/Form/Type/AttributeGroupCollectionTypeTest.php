<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Form\Type\AttributeGroupCollectionType;
use Oro\Bundle\EntityConfigBundle\Form\Type\AttributeGroupType;
use Oro\Bundle\EntityConfigBundle\Form\Type\AttributeMultiSelectType;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class AttributeGroupCollectionTypeTest extends FormIntegrationTestCase
{
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
                CollectionType::class => new CollectionType(),
                AttributeGroupType::class => new AttributeGroupType(),
                LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                AttributeMultiSelectType::class => new AttributeMultiSelectType($attributeManagerMock)
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
        $options = ['entry_options' =>
            [
                'attributeEntityClass' => 'EnityClass',
                'data_class' => AttributeGroup::class
            ]
        ];
        $form = $this->factory->create(AttributeGroupCollectionType::class, [$existingEntity], $options);

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
        $formType = new AttributeGroupCollectionType();
        $this->assertSame(AttributeGroupCollectionType::NAME, $formType->getName());
    }

    public function testGetParent()
    {
        $formType = new AttributeGroupCollectionType();
        $this->assertSame(CollectionType::class, $formType->getParent());
    }
}
