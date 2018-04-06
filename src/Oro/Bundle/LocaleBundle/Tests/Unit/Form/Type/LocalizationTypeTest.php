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
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class LocalizationTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const DATA_CLASS = Localization::class;

    /** @var LocalizationType */
    protected $formType;

    /** @var array */
    protected static $languages = [
        '0' => 'en',
        '1' => 'ru',
        '2' => 'en_US'
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new LocalizationType();
        $this->formType->setDataClass(static::DATA_CLASS);
        parent::setUp();
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizationType::NAME, $this->formType->getName());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param mixed $defaultData
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit($defaultData, array $submittedData, $expectedData)
    {
        $form = $this->factory->create(LocalizationType::class, $defaultData);

        $formConfig = $form->getConfig();
        $this->assertEquals(static::DATA_CLASS, $formConfig->getOption('data_class'));

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
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
                ],
                'expectedData' => $this->createLocalization('NAME', 'TITLE', '1', 'ru'),
            ],
            'with entity' => [
                'defaultData' => $localizationItem,
                'submittedData' => [
                    'name' => 'new_localization_item_name',
                    'titles' => [['string' => 'new_localization_item_title']],
                    'language' => '2',
                    'formattingCode' => 'en_US',
                    'parentLocalization' => 1,
                ],
                'expectedData' => $this->createLocalization(
                    'new_localization_item_name',
                    'new_localization_item_title',
                    '2',
                    'en_US',
                    $parent
                )
            ]
        ];
    }

    /**
     * @param string $name
     * @param string $title
     * @param string $languageId
     * @param string $formattingCode
     * @param Localization $parentLocalization
     *
     * @return Localization
     */
    protected function createLocalization(
        $name,
        $title,
        $languageId,
        $formattingCode,
        Localization $parentLocalization = null
    ) {
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
                'parentLocalization' => $parentLocalization,
            ]
        );

        $localization->setDefaultTitle($title);

        return $localization;
    }

    /**
     * @return array
     */
    protected function getExtensions()
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
                    LocalizationType::class => $this->formType,
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    LanguageSelectType::class => new EntityType($languages, LanguageSelectType::NAME),
                    FormattingSelectType::class => new FormattingSelectTypeStub(),
                    LocalizationParentSelectType::class => new EntityType(
                        [
                            '1' => $this->getEntity(Localization::class, ['id' => 1])
                        ],
                        LocalizationParentSelectType::NAME
                    ),
                ],
                [FormType::class => [new StripTagsExtension($helper)]]
            )
        ];
    }
}
