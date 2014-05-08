<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormView;

use Oro\Bundle\FormBundle\Form\Extension\RandomIdExtension;

class RandomIdExtensionTest extends \PHPUnit_Framework_TestCase
{
    const ID = 'test_id';
    /**
     * @var RandomIdExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new RandomIdExtension();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())->method('setDefaults')
            ->with(array('random_id' => false));

        $this->extension->setDefaultOptions($resolver);
    }

    /**
     * @dataProvider finishViewData
     * @param FormView $view
     * @param array $options
     * @param boolean $equal
     */
    public function testFinishView(FormView $view, array $options, $equal)
    {
        $formMock = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $isEmptyId = empty($view->vars['id']);
        $this->extension->finishView($view, $formMock, $options);

        if ($isEmptyId) {
            $this->assertTrue(empty($view->vars['id']));
        } else {
            $this->assertEquals($equal, $view->vars['id'] === self::ID);
        }
    }

    /**
     * @return array
     */
    public function finishViewData()
    {
        return array(
            'add random hash' => array(
                'view'   => $this->createView(),
                'option' => array('random_id' => true),
                'equal'  => false
            ),
            'without random hash' => array(
                'view'   => $this->createView(),
                'option' => array('random_id' => false),
                'equal'  => true
            ),
            'without id' => array(
                'view'   => $this->createView(false),
                'option' => array('random_id' => false),
                'equal'  => false
            )
        );
    }

    protected function createView($withId = true)
    {
        $result = new FormView();
        if ($withId) {
            $result->vars = array('id' => self::ID);
        }
        return $result;
    }
}
