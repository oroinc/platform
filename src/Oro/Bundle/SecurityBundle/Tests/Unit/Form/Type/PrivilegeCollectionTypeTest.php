<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;

use Oro\Bundle\SecurityBundle\Form\Type\PrivilegeCollectionType;

class PrivilegeCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var PrivilegeCollectionType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new PrivilegeCollectionType();
    }

    public function testGetName()
    {
        $this->assertEquals(PrivilegeCollectionType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('collection', $this->formType->getParent());
    }

    public function testBuildView()
    {
        $view = new FormView();
        $form = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $privilegesConfig = array('permissions' => array('VIEW', 'CREATE'));
        $options = array(
            'options' => array(
                'privileges_config' => $privilegesConfig
            ),
            'page_component_module' => 'component_name',
            'page_component_options' => ['component' => 'options'],
        );

        $expectedVars = [
            'privileges_config' => $privilegesConfig,
            'page_component_module' => $options['page_component_module'],
            'page_component_options' => $options['page_component_options'],
        ];

        $this->formType->buildView($view, $form, $options);
        foreach ($expectedVars as $key => $value) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertEquals($value, $view->vars[$key]);
        }
    }
}
