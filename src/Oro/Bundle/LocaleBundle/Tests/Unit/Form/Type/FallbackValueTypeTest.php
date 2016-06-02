<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Form\Type\FallbackValueType;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\OroRichTextTypeStub;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\TextTypeStub;

class FallbackValueTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FallbackValueType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new FallbackValueType();
    }

    protected function tearDown()
    {
        unset($this->formType);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface $translator */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        return [
            new PreloadedExtension(
                [
                    FallbackPropertyType::NAME => new FallbackPropertyType($translator),
                    TextTypeStub::NAME => new TextTypeStub(),
                    OroRichTextTypeStub::NAME => new OroRichTextTypeStub()
                ],
                []
            )
        ];
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $options
     * @param mixed $defaultData
     * @param array $viewData
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @param array $expectedOptions
     */
    public function testSubmit(
        array $options,
        $defaultData,
        array $viewData,
        $submittedData,
        $expectedData,
        array $expectedOptions
    ) {
        $form = $this->factory->create($this->formType, $defaultData, $options);

        $formConfig = $form->getConfig();
        $this->assertNull($formConfig->getOption('data_class'));
        $this->assertEquals(FallbackPropertyType::NAME, $formConfig->getOption('fallback_type'));

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($viewData, $form->getViewData());

        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
            $this->assertEquals($value, $formConfig->getOption($key));
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider()
    {
        return [
            'percent with value' => [
                'options' => [
                    'type'    => 'percent',
                    'options' => ['type' => 'integer'],
                    'group_fallback_fields' => null
                ],
                'defaultData'   => 25,
                'viewData'      => ['value' => 25, 'use_fallback' => false, 'fallback' => null],
                'submittedData' => ['value' => '55', 'use_fallback' => false, 'fallback' => ''],
                'expectedData'  => 55,
                'expectedOptions' => ['group_fallback_fields' => false]
            ],
            'text with fallback' => [
                'options' => [
                    'type'              => TextTypeStub::NAME,
                    'enabled_fallbacks' => [FallbackType::PARENT_LOCALE],
                    'group_fallback_fields' => false
                ],
                'defaultData'   => new FallbackType(FallbackType::SYSTEM),
                'viewData'      => ['value' => null, 'use_fallback' => true, 'fallback' => FallbackType::SYSTEM],
                'submittedData' => ['value' => '', 'use_fallback' => true, 'fallback' => FallbackType::PARENT_LOCALE],
                'expectedData'  => new FallbackType(FallbackType::PARENT_LOCALE),
                'expectedOptions' => ['group_fallback_fields' => false]
            ],
            'integer as null' => [
                'options' => [
                    'type' => 'integer',
                    'group_fallback_fields' => true
                ],
                'defaultData'   => null,
                'viewData'      => ['value' => null, 'use_fallback' => false, 'fallback' => null],
                'submittedData' => null,
                'expectedData'  => null,
                'expectedOptions' => ['group_fallback_fields' => true]
            ],
            'richtext with fallback' => [
                'options' => [
                    'type'              => OroRichTextTypeStub::NAME,
                    'enabled_fallbacks' => [FallbackType::PARENT_LOCALE],
                    'group_fallback_fields' => null
                ],
                'defaultData'   => new FallbackType(FallbackType::SYSTEM),
                'viewData'      => ['value' => null, 'use_fallback' => true, 'fallback' => FallbackType::SYSTEM],
                'submittedData' => ['value' => '', 'use_fallback' => true, 'fallback' => FallbackType::PARENT_LOCALE],
                'expectedData'  => new FallbackType(FallbackType::PARENT_LOCALE),
                'expectedOptions' => ['group_fallback_fields' => true]
            ]
        ];
    }

    public function testFinishView()
    {
        $groupFallbackFields = 'test value';

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $formMock */
        $formMock = $this->getMock('Symfony\Component\Form\FormInterface');

        $formView = new FormView();
        $this->formType->finishView($formView, $formMock, ['group_fallback_fields' => $groupFallbackFields]);
        $this->assertArrayHasKey('group_fallback_fields', $formView->vars);
        $this->assertEquals($groupFallbackFields, $formView->vars['group_fallback_fields']);
    }

    public function testGetName()
    {
        $this->assertEquals(FallbackValueType::NAME, $this->formType->getName());
    }
}
