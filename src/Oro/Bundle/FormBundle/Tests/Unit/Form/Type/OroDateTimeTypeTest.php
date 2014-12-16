<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;

class OroDateTimeTypeTest extends TypeTestCase
{
    /**
     * @var OroDateTimeType
     */
    private $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new OroDateTimeType();
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
            'years'            => [],
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
        $this->assertArrayHasKey($expectedKey, $view->vars);
        $this->assertEquals($expectedValue, $view->vars[$expectedKey]);
    }

    public function optionsDataProvider()
    {
        return array(
            array(
                array('placeholder' => 'some.placeholder'),
                'attr',
                array('placeholder' => 'some.placeholder'),
            ),
            array(
                array('years' => [2001, 2002, 2003]),
                'years',
                '2001:2003',
            ),
        );
    }

    /**
     * @dataProvider valuesDataProvider
     * @param string  $value
     * @param \DateTime $expectedValue
     */
    public function testSubmitValidData($value, $expectedValue)
    {
        $form = $this->factory->create($this->type);
        $form->submit($value);
        $this->assertDateTimeEquals($expectedValue, $form->getData());
    }

    public function valuesDataProvider()
    {
        return array(
            array(
                '2002-10-02T15:00:00+00:00',
                new \DateTime('2002-10-02T15:00:00+00:00')
            ),
            array(
                '2002-10-02T15:00:00Z',
                new \DateTime('2002-10-02T15:00:00Z')
            ),
            array(
                '2002-10-02T15:00:00.05Z',
                new \DateTime('2002-10-02T15:00:00.05Z')
            )
        );
    }
}
