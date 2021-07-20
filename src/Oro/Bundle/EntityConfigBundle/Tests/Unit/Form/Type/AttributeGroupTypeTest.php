<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Form\Type\AttributeGroupType;
use Oro\Bundle\EntityConfigBundle\Form\Type\AttributeMultiSelectType;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackValueType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class AttributeGroupTypeTest extends FormIntegrationTestCase
{
    private const LOCALIZATION_ID = 42;
    private const ATTRIBUTES_CHOICES = ['choice_1' => 1, 'choice_5' => 5, 'choice_15' => 15, 'choice_20' => 20];

    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $attributeManager;

    /** @var Translator|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    protected function setUp(): void
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
        $this->translator = $this->createMock(Translator::class);

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider $entityConfigProvider */
        $entityConfigProvider = $this->createMock(ConfigProvider::class);

        return [
            new PreloadedExtension(
                [
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionType(
                        $this->registry
                    ),
                    AttributeMultiSelectType::class => $this->getEntity(
                        AttributeMultiSelectType::class,
                        ['choices' => self::ATTRIBUTES_CHOICES],
                        [$this->attributeManager, $this->getTranslator()]
                    ),
                    LocalizedPropertyType::class => new LocalizedPropertyType(),
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub(
                        [
                            $this->getEntity(Localization::class, ['id' => self::LOCALIZATION_ID])
                        ]
                    ),
                    FallbackValueType::class => new FallbackValueType(),
                    FallbackPropertyType::class => new FallbackPropertyType($this->translator),
                ],
                [
                    FormType::class => [
                        new StripTagsExtensionStub($this),
                        new TooltipFormExtension($entityConfigProvider, $this->translator),
                    ]
                ]
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
        $this->assertTrue($form->isSynchronized());

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

    public function testSubmitWhenOrderedAttributeRelations(): void
    {
        $existingGroup = new AttributeGroup();
        $existingGroup->addAttributeRelation($attributeRelation5 = new AttributeGroupRelation());
        $attributeRelation5->setAttributeGroup($existingGroup);
        $attributeRelation5->setEntityConfigFieldId($attribute5Id = 5);

        $existingGroup->addAttributeRelation($attributeRelation1 = new AttributeGroupRelation());
        $attributeRelation1->setAttributeGroup($existingGroup);
        $attributeRelation1->setEntityConfigFieldId($attribute1Id = 1);

        $existingGroup->addAttributeRelation($attributeRelation15 = new AttributeGroupRelation());
        $attributeRelation15->setAttributeGroup($existingGroup);
        $attributeRelation15->setEntityConfigFieldId($attribute15Id = 15);

        $attributeRelation20 = new AttributeGroupRelation();
        $attributeRelation20->setAttributeGroup($existingGroup);
        $attributeRelation20->setEntityConfigFieldId($attribute20Id = 20);

        $submittedData = [
            'labels' => [
                'values' => [
                    'default' => 'Group Label 1',
                    'localizations' => [
                        self::LOCALIZATION_ID => [
                            'value' => 'Group Label 2',
                        ],
                    ],
                ],
            ],
            'isVisible' => true,
            'attributeRelations' => [$attribute1Id, $attribute5Id, $attribute20Id, $attribute15Id],
        ];

        $form = $this->factory->create(
            AttributeGroupType::class,
            $existingGroup,
            ['attributeEntityClass' => 'EntityClass']
        );

        $this->assertSame(
            [
                'choice_5' => $attribute5Id,
                'choice_1' => $attribute1Id,
                'choice_15' => $attribute15Id,
                'choice_20' => $attribute20Id,
            ],
            $form->get('attributeRelations')->getConfig()->getOption('choices')
        );

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $entity = new AttributeGroup();

        $entity->addLabel(
            $this->createLocalizedValue(
                'Group Label 2_stripped',
                null,
                $this->getEntity(Localization::class, ['id' => self::LOCALIZATION_ID])
            )
        );
        $entity->addLabel($this->createLocalizedValue('Group Label 1_stripped'));

        $entity->addAttributeRelation($attributeRelation1);
        $entity->addAttributeRelation($attributeRelation5);
        $entity->addAttributeRelation($attributeRelation20);
        $entity->addAttributeRelation($attributeRelation15);

        $formData = $form->getData();
        $this->assertEquals($entity, $formData);
    }

    public function testPostSetDataWhenNoData(): void
    {
        $formEvent = $this->createMock(FormEvent::class);
        $formEvent
            ->expects($this->once())
            ->method('getData')
            ->willReturn(null);

        $formEvent
            ->expects($this->never())
            ->method('getForm');

        (new AttributeGroupType())->postSetData($formEvent);
    }

    /**
     * @dataProvider postSetDataDataProvider
     */
    public function testPostSetData(AttributeGroup $attributeGroup, array $choices, array $expectedChoices): void
    {
        $formEvent = new FormEvent($form = $this->createMock(FormInterface::class), $attributeGroup);

        $form
            ->expects($this->once())
            ->method('get')
            ->with('attributeRelations')
            ->willReturn($attributeRelationsForm = $this->createMock(FormInterface::class));

        $attributeRelationsForm
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig = $this->createMock(FormConfigInterface::class));

        $formConfig
            ->expects($this->once())
            ->method('getOption')
            ->with('choices')
            ->willReturn($choices);

        $expectedOptions = [
            'label' => 'oro.entity_config.attribute_group.attribute_relations.label',
            'configs' => [
                'component' => 'attribute-autocomplete',
            ],
            'attributeGroup' => $attributeGroup,
            'by_reference' => false,
            'choices' => $expectedChoices,
        ];

        $form
            ->expects($this->once())
            ->method('add')
            ->with('attributeRelations', AttributeMultiSelectType::class, $expectedOptions);

        (new AttributeGroupType())->postSetData($formEvent);
    }

    public function postSetDataDataProvider(): array
    {
        $attributeGroup = new AttributeGroup();
        $attributeGroup->addAttributeRelation($attributeRelation5 = new AttributeGroupRelation());
        $attributeRelation5->setAttributeGroup($attributeGroup);
        $attributeRelation5->setEntityConfigFieldId($attribute5Id = 5);

        $attributeGroup->addAttributeRelation($attributeRelation1 = new AttributeGroupRelation());
        $attributeRelation1->setAttributeGroup($attributeGroup);
        $attributeRelation1->setEntityConfigFieldId($attribute1Id = 1);

        return [
            'attribute relations are not empty, choices are reordered' => [
                'attributeGroup' => $attributeGroup,
                'choices' => self::ATTRIBUTES_CHOICES,
                'expectedChoices' => ['choice_5' => 5, 'choice_1' => 1, 'choice_15' => 15, 'choice_20' => 20],
            ],
            'choices are empty' => [
                'attributeGroup' => $attributeGroup,
                'choices' => [],
                'expectedChoices' => [],
            ],
        ];
    }

    public function testPostSetDataWhenNoAttributeRelations(): void
    {
        $formEvent = new FormEvent(
            $form = $this->createMock(FormInterface::class),
            $attributeGroup = new AttributeGroup()
        );

        $expectedOptions = [
            'label' => 'oro.entity_config.attribute_group.attribute_relations.label',
            'configs' => [
                'component' => 'attribute-autocomplete',
            ],
            'attributeGroup' => $attributeGroup,
            'by_reference' => false,
        ];

        $form
            ->expects($this->once())
            ->method('add')
            ->with('attributeRelations', AttributeMultiSelectType::class, $expectedOptions);

        (new AttributeGroupType())->postSetData($formEvent);
    }

    public function testGetName()
    {
        $formType = new AttributeGroupType();
        $this->assertEquals(AttributeGroupType::NAME, $formType->getName());
    }
}
