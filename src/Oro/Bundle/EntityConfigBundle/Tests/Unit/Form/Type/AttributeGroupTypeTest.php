<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Form\Type\AttributeGroupType;
use Oro\Bundle\EntityConfigBundle\Form\Type\AttributeMultiSelectType;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackValueType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Translation\TranslatorInterface;

class AttributeGroupTypeTest extends FormIntegrationTestCase
{
    const LOCALIZATION_ID = 42;

    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $attributeManager;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    protected function setUp()
    {
        $repositoryLocalization = $this->createMock(ObjectRepository::class);
        $repositoryLocalization->expects($this->any())
            ->method('find')
            ->willReturnCallback(
                function ($id) {
                    return $this->getEntity(Localization::class, ['id' => $id]);
                }
            );

        $repositoryLocalizedFallbackValue = $this->createMock(ObjectRepository::class);
        $repositoryLocalizedFallbackValue->expects($this->any())
            ->method('find')
            ->willReturnCallback(
                function ($id) {
                    return $this->getEntity(LocalizedFallbackValue::class, ['id' => $id]);
                }
            );

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    ['OroLocaleBundle:Localization', null, $repositoryLocalization],
                    ['OroLocaleBundle:LocalizedFallbackValue', null, $repositoryLocalizedFallbackValue],
                ]
            );

        $this->attributeManager = $this->createMock(AttributeManager::class);
        $this->attributeManager->expects($this->any())->method('getActiveAttributesByClass')->willReturn([]);

        $this->translator = $this->createMock(TranslatorInterface::class);

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
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionType(
                        $this->registry
                    ),
                    AttributeMultiSelectType::class => new AttributeMultiSelectType($this->attributeManager),
                    LocalizedPropertyType::class => new LocalizedPropertyType(),
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub(
                        [
                            $this->getEntity(Localization::class, ['id' => self::LOCALIZATION_ID])
                        ]
                    ),
                    FallbackValueType::class => new FallbackValueType(),
                    FallbackPropertyType::class => new FallbackPropertyType($this->translator),
                ],
                [FormType::class => [new StripTagsExtensionStub($this->createMock(HtmlTagHelper::class))]]
            ),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * @param string|null $string
     * @param string|null $text
     * @param Localization|null $localization
     *
     * @return LocalizedFallbackValue
     */
    protected function createLocalizedValue($string = null, $text = null, Localization $localization = null)
    {
        $value = new LocalizedFallbackValue();
        $value->setString($string)
            ->setText($text)
            ->setLocalization($localization);

        return $value;
    }

    public function testSubmit()
    {
        $submittedData = [
            'labels' => [
                'values' => [
                    'default' => 'Group Label 1',
                    'localizations' => [
                        self::LOCALIZATION_ID => [
                            'value' => 'Group Label 2'
                        ]
                    ]
                ]
            ],
            'isVisible' => true
        ];

        $form = $this->factory->create(
            AttributeGroupType::class,
            new AttributeGroup(),
            ['attributeEntityClass' => 'EntityClass']
        );

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $entity = new AttributeGroup();
        $entity->addLabel(
            $this->createLocalizedValue(
                'Group Label 2_stripped',
                null,
                $this->getEntity(Localization::class, ['id' => self::LOCALIZATION_ID])
            )
        );
        $entity->addLabel($this->createLocalizedValue('Group Label 1_stripped'));

        $formData = $form->getData();
        $this->assertEquals($entity, $formData);
    }

    public function testGetName()
    {
        $formType = new AttributeGroupType();
        $this->assertEquals(AttributeGroupType::NAME, $formType->getName());
    }
}
