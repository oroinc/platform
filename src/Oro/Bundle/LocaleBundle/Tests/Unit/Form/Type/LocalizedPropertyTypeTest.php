<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\LocaleBundle\Form\DataTransformer\MultipleValueTransformer;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackValueType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\PercentTypeStub;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocalizedPropertyTypeTest extends AbstractLocalizedType
{
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $localizationCollection = new LocalizationCollectionType($this->registry);
        $localizationCollection->setDataClass(self::LOCALIZATION_CLASS);

        return [
            new PreloadedExtension(
                [
                    new FallbackPropertyType($this->createMock(TranslatorInterface::class)),
                    new FallbackValueType(),
                    $localizationCollection,
                    new PercentTypeStub(),
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            )
        ];
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $options,
        ?array $defaultData,
        array $viewData,
        ?array $submittedData,
        array $expectedData
    ) {
        $this->setRegistryExpectations();

        $form = $this->factory->create(LocalizedPropertyType::class, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());
        foreach ($viewData as $field => $data) {
            $this->assertEquals($data, $form->get($field)->getViewData());
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'text with null data' => [
                'options' => ['entry_type' => TextType::class],
                'defaultData' => null,
                'viewData' => [
                    LocalizedPropertyType::FIELD_DEFAULT => null,
                    LocalizedPropertyType::FIELD_LOCALIZATIONS => [
                        1 => new FallbackType(FallbackType::SYSTEM),
                        2 => new FallbackType(FallbackType::PARENT_LOCALIZATION),
                        3 => new FallbackType(FallbackType::PARENT_LOCALIZATION),
                    ]
                ],
                'submittedData' => null,
                'expectedData' => [
                    null => null,
                    1    => null,
                    2    => null,
                    3    => null,
                ],
            ],
            'percent with full data' => [
                'options' => ['entry_type' => PercentTypeStub::class, 'entry_options' => ['type' => 'integer']],
                'defaultData' => [
                    null => 5,
                    1    => 10,
                    2    => null,
                    3    => new FallbackType(FallbackType::PARENT_LOCALIZATION),
                ],
                'viewData' => [
                    LocalizedPropertyType::FIELD_DEFAULT => 5,
                    LocalizedPropertyType::FIELD_LOCALIZATIONS => [
                        1 => 10,
                        2 => new FallbackType(FallbackType::PARENT_LOCALIZATION),
                        3 => new FallbackType(FallbackType::PARENT_LOCALIZATION),
                    ]
                ],
                'submittedData' => [
                    LocalizedPropertyType::FIELD_DEFAULT => '10',
                    LocalizedPropertyType::FIELD_LOCALIZATIONS => [
                        1 => ['value' => '', 'fallback' => FallbackType::SYSTEM, 'use_fallback' => true],
                        2 => ['value' => '5', 'fallback' => ''],
                        3 => ['value' => '', 'fallback' => FallbackType::PARENT_LOCALIZATION, 'use_fallback' => true],
                    ]
                ],
                'expectedData' => [
                    null => 10,
                    1    => new FallbackType(FallbackType::SYSTEM),
                    2    => 5,
                    3    => new FallbackType(FallbackType::PARENT_LOCALIZATION),
                ],
            ],
        ];
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(function (array $options) {
                    self::assertEquals([], $options['entry_options']);
                    self::assertFalse($options['exclude_parent_localization']);

                    return true;
                })
            );

        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['entry_type']);
        $formType = new LocalizedPropertyType();
        $formType->configureOptions($resolver);
    }

    public function testBuildForm()
    {
        $type = 'form_text';
        $options = ['key' => 'value'];

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [
                    LocalizedPropertyType::FIELD_DEFAULT,
                    $type,
                    [
                        'key' => 'value',
                        'label' => 'oro.locale.fallback.value.default'
                    ]
                ],
                [
                    LocalizedPropertyType::FIELD_LOCALIZATIONS,
                    LocalizationCollectionType::class,
                    [
                        'entry_type' => $type,
                        'entry_options' => $options,
                        'exclude_parent_localization' => false,
                        'use_tabs' => true,
                    ]
                ]
            )
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('addViewTransformer')
            ->with(
                new MultipleValueTransformer(
                    LocalizedPropertyType::FIELD_DEFAULT,
                    LocalizedPropertyType::FIELD_LOCALIZATIONS
                )
            )
            ->willReturnSelf();

        $formType = new LocalizedPropertyType();
        $formType->buildForm(
            $builder,
            [
                'entry_type' => $type,
                'entry_options' => $options,
                'exclude_parent_localization' => false,
                'use_tabs' => true,
            ]
        );
    }

    public function testFinishView(): void
    {
        $formView = new FormView();
        $formView->vars['block_prefixes'] = ['form', '_custom_block_prefix'];

        $formType = new LocalizedPropertyType();
        $formType->finishView(
            $formView,
            $this->createMock(FormInterface::class),
            ['use_tabs' => true]
        );

        $this->assertEquals(
            ['form', 'oro_locale_localized_property_tabs', '_custom_block_prefix'],
            $formView->vars['block_prefixes']
        );
    }

    public function testGetName()
    {
        $formType = new LocalizedPropertyType();
        $this->assertEquals(LocalizedPropertyType::NAME, $formType->getName());
    }
}
