<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType;
use Oro\Bundle\LocaleBundle\Model\FallbackType;

class FallbackPropertyTypeTest extends FormIntegrationTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var FallbackPropertyType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        /** @var TranslatorInterface $translator */
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->with('oro.locale.fallback.type.parent_localization')
            ->willReturn('Parent Localization');

        $this->formType = new FallbackPropertyType($this->translator);
    }

    protected function tearDown()
    {
        unset($this->translator, $this->formType);
    }

    /**
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param mixed $submittedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $inputOptions, array $expectedOptions, $submittedData)
    {
        $form = $this->factory->create($this->formType, null, $inputOptions);

        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
            $this->assertEquals($value, $formConfig->getOption($key));
        }

        $this->assertNull($form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($submittedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'default options' => [
                'inputOptions' => [],
                'expectedOptions' => [
                    'required' => false,
                    'empty_value' => false,
                    'choices' => [
                        FallbackType::SYSTEM => 'oro.locale.fallback.type.default',
                    ],
                ],
                'submittedData' => FallbackType::SYSTEM,
            ],
            'parent localization' => [
                'inputOptions' => [
                    'enabled_fallbacks' => [FallbackType::PARENT_LOCALIZATION]
                ],
                'expectedOptions' => [
                    'required' => false,
                    'empty_value' => false,
                    'choices' => [
                        FallbackType::PARENT_LOCALIZATION => 'oro.locale.fallback.type.parent_localization',
                        FallbackType::SYSTEM => 'oro.locale.fallback.type.default',
                    ],
                ],
                'submittedData' => FallbackType::PARENT_LOCALIZATION,
            ],
            'parent localization with suffix' => [
                'inputOptions' => [
                    'enabled_fallbacks' => [FallbackType::PARENT_LOCALIZATION],
                    'localization' => 'en_US',
                    'parent_localization' => 'en',
                ],
                'expectedOptions' => [
                    'required' => false,
                    'empty_value' => false,
                    'choices' => [
                        FallbackType::PARENT_LOCALIZATION => 'en [Parent Localization]',
                        FallbackType::SYSTEM => 'oro.locale.fallback.type.default',
                    ],
                ],
                'submittedData' => FallbackType::PARENT_LOCALIZATION,
            ],
            'custom choices' => [
                'inputOptions' => [
                    'choices' => [0 => '0', 1 => '1'],
                ],
                'expectedOptions' => [
                    'choices' => [0 => '0', 1 => '1'],
                ],
                'submittedData' => null,
            ],
        ];
    }

    public function testFinishView()
    {
        $localizationCode = 'en_US';
        $parentCode = 'en';

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $formView = new FormView();
        $this->formType->finishView($formView, $form, [
            'localization' => $localizationCode,
            'parent_localization' => $parentCode,
        ]);

        $this->assertArrayHasKey('attr', $formView->vars);
        $this->assertArrayHasKey('data-localization', $formView->vars['attr']);
        $this->assertArrayHasKey('data-parent-localization', $formView->vars['attr']);
        $this->assertEquals($localizationCode, $formView->vars['attr']['data-localization']);
        $this->assertEquals($parentCode, $formView->vars['attr']['data-parent-localization']);
    }

    public function testGetName()
    {
        $this->assertEquals(FallbackPropertyType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }
}
