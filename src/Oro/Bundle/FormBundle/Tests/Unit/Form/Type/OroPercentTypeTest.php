<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroPercentType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroPercentTypeTest extends FormIntegrationTestCase
{
    /**
     * @var string
     */
    protected $locale;

    /**
     * @var OroPercentType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->locale = \Locale::getDefault();
        \Locale::setDefault('en');
        $this->formType = new OroPercentType();
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Locale::setDefault($this->locale);
        unset($this->locale);
        unset($this->formType);
    }

    public function testGetName()
    {
        $this->assertEquals(OroPercentType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('percent', $this->formType->getParent());
    }

    /**
     * @param float $data
     * @param array $viewData
     * @param array $options
     * @dataProvider buildFormDataProvider
     */
    public function testBuildForm(
        $data,
        array $viewData,
        array $options = array()
    ) {
        $form = $this->factory->create($this->formType, $data, $options);
        $view = $form->createView();

        foreach ($viewData as $key => $value) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertEquals($value, $view->vars[$key]);
        }
    }

    /**
     * @return array
     */
    public function buildFormDataProvider()
    {
        return array(
            'default' => array(
                'data'     => 0.1122,
                'viewData' => array(
                    'value' => '11.22'
                ),
            ),
            'custom precision' => array(
                'data'     => 0.1122,
                'viewData' => array(
                    'value' => '11'
                ),
                'options' => array(
                    'precision' => 0
                ),
            ),
        );
    }

    /**
     * @param float $data
     * @param array $expectedData
     * @param array $options
     * @dataProvider submitFormDataProvider
     */
    public function testSubmitForm(
        $data,
        $expectedData,
        array $options = array()
    ) {
        $form = $this->factory->create($this->formType, null, $options);
        $form->submit($data);
        self::assertTrue($form->isSynchronized());
        self::assertSame($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitFormDataProvider()
    {
        return [
            'unspecified precision, with numbers after decimal point'                                       => [
                'data'         => (string)123.45,
                'expectedData' => 1.2345
            ],
            'unspecified precision, without numbers after decimal point'                                    => [
                'data'         => (string)123,
                'expectedData' => 1.23
            ],
            'unspecified precision, without numbers after decimal point, value can be converted to integer' => [
                'data'         => (string)100,
                'expectedData' => (float)1
            ],
            'zero precision, with numbers after decimal point'                                              => [
                'data'         => (string)123.45,
                'expectedData' => 1.2345,
                'options'      => ['precision' => 0]
            ],
            'zero precision, without numbers after decimal point'                                           => [
                'data'         => (string)123,
                'expectedData' => 1.23,
                'options'      => ['precision' => 0]
            ],
            'zero precision, without numbers after decimal point, value can be converted to integer'        => [
                'data'         => 100,
                'expectedData' => (float)1,
                'options'      => ['precision' => 0]
            ],
            'custom precision, with numbers after decimal point'                                            => [
                'data'         => (string)123.45,
                'expectedData' => 1.2345,
                'options'      => ['precision' => 1]
            ],
            'custom precision, without numbers after decimal point'                                         => [
                'data'         => (string)123,
                'expectedData' => 1.23,
                'options'      => ['precision' => 1]
            ],
            'custom precision, without numbers after decimal point, value can be converted to integer'      => [
                'data'         => (string)100,
                'expectedData' => (float)1,
                'options'      => ['precision' => 1]
            ],
        ];
    }
}
