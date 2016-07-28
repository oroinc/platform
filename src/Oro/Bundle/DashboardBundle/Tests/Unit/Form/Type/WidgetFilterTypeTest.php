<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetFilterType;
use Oro\Bundle\QueryDesignerBundle\Form\Type\FilterType;

class WidgetFilterTypeTest extends TypeTestCase
{
    /** @var WidgetFilterType */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->formType = new WidgetFilterType();
        parent::setUp();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_dashboard_query_filter', $this->formType->getName());
    }

    public function testSubmitValidData()
    {
        $formData = [
            'entity'     => 'TestClass',
            'definition' => '{"filters":[]}'
        ];
        $form     = $this->factory->create(new WidgetFilterType(), null, ['entity' => 'TestClass']);
        $form->submit($formData);

        $this->assertTrue($form->isValid());
        $this->assertEquals(
            $formData,
            $form->getData()
        );
    }

    public function testView()
    {
        $options = [
            'entity'      => 'TestClass',
            'widgetType'  => 'test_widget',
            'collapsible' => true,
            'collapsed'   => false
        ];
        $form    = $this->factory->create(new WidgetFilterType(), null, $options);
        $view    = $form->createView();
        $this->assertEquals('test_widget', $view->vars['widgetType']);
        $this->assertTrue($view->vars['collapsible']);
        $this->assertFalse($view->vars['collapsed']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $preLoadedExtension = new PreloadedExtension(
            ['oro_query_designer_filter' => new FilterType()],
            []
        );

        return array_merge(parent::getExtensions(), [$preLoadedExtension]);
    }
}
