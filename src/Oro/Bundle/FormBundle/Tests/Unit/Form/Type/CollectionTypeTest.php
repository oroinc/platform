<?php
namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;

class CollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CollectionType
     */
    protected $type;

    /**
     * Setup test env
     */
    protected function setUp()
    {
        $this->type = new CollectionType();
    }

    public function testBuildForm()
    {
        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');

        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Oro\Bundle\FormBundle\Form\EventListener\CollectionTypeSubscriber'));

        $options = array();
        $this->type->buildForm($builder, $options);
    }

    /**
     * @dataProvider buildViewDataProvider
     */
    public function testBuildView($options, $expectedVars)
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $view = new FormView();

        $this->type->buildView($view, $form, $options);

        foreach ($expectedVars as $key => $val) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertEquals($val, $view->vars[$key]);
        }
    }

    public function buildViewDataProvider()
    {
        return [
            [
                'options'      => [
                    'show_form_when_empty' => true,
                    'can_add_and_delete'   => true
                ],
                'expectedVars' => [
                    'show_form_when_empty' => true,
                    'can_add_and_delete'   => true
                ],
            ],
            [
                'options'      => [
                    'show_form_when_empty' => false,
                    'can_add_and_delete'   => false
                ],
                'expectedVars' => [
                    'show_form_when_empty' => false,
                    'can_add_and_delete'   => false
                ],
            ],
            [
                'options'      => [
                    'show_form_when_empty' => true,
                    'can_add_and_delete'   => false
                ],
                'expectedVars' => [
                    'show_form_when_empty' => false,
                    'can_add_and_delete'   => false
                ],
            ],
            [
                'options'      => [
                    'show_form_when_empty' => false,
                    'can_add_and_delete'   => true
                ],
                'expectedVars' => [
                    'show_form_when_empty' => false,
                    'can_add_and_delete'   => true
                ],
            ],
        ];
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals('collection', $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_collection', $this->type->getName());
    }
}
