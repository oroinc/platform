<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\EntityConfigBundle\Form\Type\AttributeFamilyType;
use Oro\Bundle\EntityConfigBundle\Form\Type\AttributeMultiSelectType;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\AttributeFamilyStub;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\ImageTypeStub;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class AttributeFamilyTypeTest extends FormIntegrationTestCase
{
    /**
     * @var AttributeFamilyType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new AttributeFamilyType($this->getTranslator());
        parent::setUp();
    }

    public function testSubmitForm()
    {
        $submittedData = [
            'code' => 'uniqueCode',
            'labels' => [
                ['string' => 'first name']
            ],
            'isEnabled' => true,
        ];

        $options = ['attributeEntityClass' => 'EnityClass'];
        $form = $this->factory->create(AttributeFamilyType::class, new AttributeFamilyStub(), $options);

        $form->submit($submittedData);
        $this->assertTrue($form->isSynchronized());

        $formView = $form->createView();
        $children = $formView->children;

        $this->assertArrayHasKey('code', $children);
        $this->assertArrayHasKey('labels', $children);
        $this->assertArrayHasKey('isEnabled', $children);
        $this->assertArrayHasKey('image', $children);
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
            new PreloadedExtension(
                [
                    AttributeFamilyType::class => $this->type,
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    ImageType::class => new ImageTypeStub(),
                    AttributeMultiSelectType::class => new AttributeMultiSelectType($attributeManagerMock),
                ],
                [
                    FormType::class => [
                        new DataBlockExtension()
                    ]
                ]
            ),
            $this->getValidatorExtension()
        ];
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(
            'oro_attribute_family',
            $this->type->getBlockPrefix()
        );
    }
}
