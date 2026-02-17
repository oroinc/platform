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
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class LocalizationTypeTest extends FormIntegrationTestCase
{
    private const DATA_CLASS = Localization::class;

    private LocalizationType $formType;

    private static array $languages = [
        '0' => 'en',
        '1' => 'ru',
        '2' => 'en_US'
    ];

    #[\Override]
    protected function setUp(): void
    {
        $this->formType = new LocalizationType();
        $this->formType->setDataClass(self::DATA_CLASS);
        parent::setUp();
    }

    public function testGetName(): void
    {
        self::assertEquals(LocalizationType::NAME, $this->formType->getName());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(?Localization $defaultData, array $submittedData, Localization $expectedData): void
    {
        $form = $this->factory->create(LocalizationType::class, $defaultData);

        $formConfig = $form->getConfig();
        self::assertEquals(self::DATA_CLASS, $formConfig->getOption('data_class'));

        self::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        $localizationItem = $this->createLocalization('name', 'title', 0, 'en');
        $parent = new Localization();
        $parent->setId(1);

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
        ?Localization $parentLocalization = null
    ): Localization {
        $language = new Language();
        ReflectionUtil::setId($language, (int)$languageId);
        $language->setCode((string)self::$languages[$languageId]);

        $localization = new Localization();
        $localization->setName($name);
        $localization->setLanguage($language);
        $localization->setFormattingCode($formattingCode);
        $localization->setRtlMode($rtlMode);
        $localization->setParentLocalization($parentLocalization);
        $localization->setDefaultTitle($title);

        return $localization;
    }

    #[\Override]
    protected function getExtensions(): array
    {
        $localization = new Localization();
        $localization->setId(1);

        $languages = [];
        foreach (self::$languages as $id => $code) {
            $language = new Language();
            ReflectionUtil::setId($language, (int)$id);
            $language->setCode((string)$code);
            $languages[$id] = $language;
        }

        $helper = $this->createMock(HtmlTagHelper::class);
        $helper->expects(self::any())
            ->method('stripTags')
            ->willReturnArgument(0);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    LanguageSelectType::class => new EntityTypeStub($languages),
                    FormattingSelectType::class => new FormattingSelectTypeStub(),
                    LocalizationParentSelectType::class => new EntityTypeStub(['1' => $localization])
                ],
                [
                    FormType::class => [
                        new StripTagsExtension(
                            TestContainerBuilder::create()->add(HtmlTagHelper::class, $helper)->getContainer($this)
                        )
                    ]
                ]
            )
        ];
    }
}
