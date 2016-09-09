<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Form\Type\FallbackValueType;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\PercentTypeStub;

class LocalizedPropertyTypeTest extends AbstractLocalizedType
{
    /**
     * @var LocalizedPropertyType
     */
    protected $formType;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        parent::setUp();

        $this->formType = new LocalizedPropertyType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $localizationCollection = new LocalizationCollectionType($this->registry);
        $localizationCollection->setDataClass(self::LOCALIZATION_CLASS);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface $translator */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        return [
            new PreloadedExtension(
                [
                    FallbackPropertyType::NAME => new FallbackPropertyType($translator),
                    FallbackValueType::NAME => new FallbackValueType(),
                    LocalizationCollectionType::NAME => $localizationCollection,
                    PercentTypeStub::NAME => new PercentTypeStub(),
                ],
                []
            )
        ];
    }

    /**
     * @param array $options
     * @param mixed $defaultData
     * @param mixed $viewData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $options, $defaultData, $viewData, $submittedData, $expectedData)
    {
        $this->setRegistryExpectations();

        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());
        foreach ($viewData as $field => $data) {
            $this->assertEquals($data, $form->get($field)->getViewData());
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'text with null data' => [
                'options' => ['type' => 'text'],
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
                'options' => ['type' => PercentTypeStub::NAME, 'options' => ['type' => 'integer']],
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
                        2 => null,
                        3 => new FallbackType(FallbackType::PARENT_LOCALIZATION),
                    ]
                ],
                'submittedData' => [
                    LocalizedPropertyType::FIELD_DEFAULT => '10',
                    LocalizedPropertyType::FIELD_LOCALIZATIONS => [
                        1 => ['value' => '', 'fallback' => FallbackType::SYSTEM],
                        2 => ['value' => '5', 'fallback' => ''],
                        3 => ['value' => '', 'fallback' => FallbackType::PARENT_LOCALIZATION],
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

    public function testGetName()
    {
        $this->assertEquals(LocalizedPropertyType::NAME, $this->formType->getName());
    }
}
