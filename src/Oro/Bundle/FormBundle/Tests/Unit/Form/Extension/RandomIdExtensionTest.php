<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Oro\Bundle\FormBundle\Form\Extension\RandomIdExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RandomIdExtensionTest extends \PHPUnit_Framework_TestCase
{
    const ID = 'test_id';
    /**
     * @var RandomIdExtension
     */
    protected $randomExtension;

    protected function setUp()
    {
        $this->randomExtension = new RandomIdExtension(new OptionsResolver());
    }

    public function testSetDefaultOptions()
    {
        /** @var OptionsResolver $resolver */
        $resolver = new OptionsResolver();
        $this->randomExtension->setDefaultOptions($resolver);

        $defaultOptions = $this->readAttribute($resolver, 'defaultOptions');
        $options        = $this->readAttribute($defaultOptions, 'options');

        $this->assertFalse($options['random_id']);
    }

    /**
     * @dataProvider finishViewData
     * @param FormView $view
     * @param array $options
     * @param boolean $equal
     */
    public function testFinishView(FormView $view, array $options, $equal)
    {
        /** @var FormInterface $formMock */
        $formMock = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $isEmptyId = empty($view->vars['id']);
        $this->randomExtension->finishView($view, $formMock, $options);

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
