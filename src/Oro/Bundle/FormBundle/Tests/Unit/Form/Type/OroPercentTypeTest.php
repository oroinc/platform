<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\OroPercentType;

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
}
