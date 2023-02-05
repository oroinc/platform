<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\LocaleBundle\Form\Type\FormattingSelectType;
use Oro\Bundle\LocaleBundle\Form\Type\LanguageSelectType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationParentSelectType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\Localization;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\FormattingSelectTypeStub;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class LocalizationTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    private const DATA_CLASS = Localization::class;

    /** @var LocalizationType */
    private $formType;

    private static array $languages = [
        '0' => 'en',
        '1' => 'ru',
        '2' => 'en_US'
    ];

    protected function setUp(): void
    {
        $this->formType = new LocalizationType();
        $this->formType->setDataClass(self::DATA_CLASS);
        parent::setUp();
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizationType::NAME, $this->formType->getName());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(?Localization $defaultData, array $submittedData, Localization $expectedData)
    {
        $form = $this->factory->create(LocalizationType::class, $defaultData);

        $formConfig = $form->getConfig();
        $this->assertEquals(self::DATA_CLASS, $formConfig->getOption('data_class'));

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        $localizationItem = $this->createLocalization('name', 'title', 0, 'en');
        $parent = $this->getEntity(Localization::class, ['id' => 1]);

        return [
            'without entity' => [
                'defaultData' => null,
                'submittedData' => [
                    'name' => 'NAME',
                    'titles' => [['string' => 'TITLE']],
                    'language' => '1',
                    'formattingCode' => 'ru',
                    'rtlMode' => true,
                ],
                'expectedData' => $this->createLocalization('NAME', 'TITLE', '1', 'ru', true),
            ],
            'with entity' => [
                'defaultData' => $localizationItem,
                'submittedData' => [
                    'name' => 'new_localization_item_name',
                    'titles' => [['string' => 'new_localization_item_title']],
                    'language' => '2',
                    'formattingCode' => 'en_US',
                    'rtlMode' => true,
                    'parentLocalization' => 1,
                ],
                'expectedData' => $this->createLocalization(
                    'new_localization_item_name',
                    'new_localization_item_title',
                    '2',
                    'en_US',
                    true,
                    $parent
                )
            ]
        ];
    }

    private function createLocalization(
        string $name,
        string $title,
        string $languageId,
        string $formattingCode,
        bool $rtlMode = false,
        Localization $parentLocalization = null
    ): Localization {
        /** @var Localization $localization */
        $localization = $this->getEntity(
            Localization::class,
            [
                'name' => $name,
                'language' => $this->getEntity(
                    Language::class,
                    ['id' => (int)$languageId, 'code' => (string)self::$languages[$languageId]]
                ),
                'formattingCode' => $formattingCode,
                'rtlMode' => $rtlMode,
                'parentLocalization' => $parentLocalization,
            ]
        );

        $localization->setDefaultTitle($title);

        return $localization;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $languages = [];
        foreach (self::$languages as $id => $code) {
            $languages[$id] = $this->getEntity(Language::class, ['id' => (int)$id, 'code' => (string)$code]);
        }

        $helper = $this->createMock(HtmlTagHelper::class);
        $helper->expects($this->any())
            ->method('stripTags')
            ->willReturnArgument(0);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    LanguageSelectType::class => new EntityTypeStub($languages),
                    FormattingSelectType::class => new FormattingSelectTypeStub(),
                    LocalizationParentSelectType::class => new EntityTypeStub([
                        '1' => $this->getEntity(Localization::class, ['id' => 1])
                    ]),
                ],
                [
                    FormType::class => [
                        new StripTagsExtension(
                            TestContainerBuilder::create()
                                ->add('oro_ui.html_tag_helper', $helper)
                                ->getContainer($this)
                        )
                    ]
                ]
            )
        ];
    }
}
