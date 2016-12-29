<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Form\Type\AttributeGroupType;
use Oro\Bundle\EntityConfigBundle\Form\Type\AttributeMultiSelectType;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;

use Symfony\Component\Form\PreloadedExtension;

class AttributeGroupTypeTest extends FormIntegrationTestCase
{
    /** @var AttributeGroupType */
    private $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new AttributeGroupType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $attributeManagerMock = $this->getMockBuilder(AttributeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $attributeManagerMock->expects($this->any())->method('getActiveAttributesByClass')->willReturn([]);

        return [
            new PreloadedExtension(
                [
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionTypeStub(),
                    AttributeMultiSelectType::NAME => new AttributeMultiSelectType($attributeManagerMock),
                    'genemu_jqueryselect2_choice' => new Select2Type('choice'),
                ],
                []
            )
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
        $submittedData =  ['labels' => [
            ['string' => 'Group Label 1'],
            ['string' => 'Group Label 2'],
        ]];
        $entity = new AttributeGroup();
        $form = $this->factory->create($this->formType, $entity, ['attributeEntityClass' => 'EntityClass']);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $entity->addLabel($this->createLocalizedValue('Group Label 1'));
        $entity->addLabel($this->createLocalizedValue('Group Label 2'));

        $formData = $form->getData();
        $this->assertEquals($entity, $formData);
    }

    public function testGetName()
    {
        $this->assertEquals(AttributeGroupType::NAME, $this->formType->getName());
    }
}
