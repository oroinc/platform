<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroDateTimeTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OroDateTimeType
     */
    private $type;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeSettings;

    protected function setUp()
    {
        parent::setUp();

        $this->localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->setMethods(array('getLocale', 'getCurrency', 'getCurrencySymbolByCurrency'))
            ->getMock();

        $this->type = new OroDateTimeType($this->localeSettings);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_datetime', $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('datetime', $this->type->getParent());
    }

    public function testSetDefaultOptions()
    {
        $expectedOptions = array(
            'model_timezone'   => 'UTC',
            'view_timezone'    => 'UTC',
            'format'           => DateTimeType::HTML5_FORMAT,
            'widget'           => 'single_text',
            'placeholder'      => 'oro.form.click_here_to_select',
        );

        $form = $this->factory->create($this->type);
        $form->submit((new \DateTime()));

        $options = $form->getConfig()->getOptions();
        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $options);
            $this->assertEquals($expectedValue, $options[$name]);
        }
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array  $options
     * @param string $expectedKey
     * @param mixed  $expectedValue
     */
    public function testFinishView($options, $expectedKey, $expectedValue)
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()->getMock();

        $view = new FormView();
        $this->type->finishView($view, $form, $options);
        $this->assertArrayHasKey($expectedKey, $view->vars['attr']);
        $this->assertEquals($expectedValue, $view->vars['attr'][$expectedKey]);
    }

    public function optionsDataProvider()
    {
        return array(
            array(
                array('placeholder' => 'some.placeholder'),
                'placeholder',
                'some.placeholder',
            ),
        );
    }
}
