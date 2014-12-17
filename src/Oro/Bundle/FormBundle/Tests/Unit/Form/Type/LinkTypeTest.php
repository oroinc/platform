<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\LinkType;

class LinkTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LinkType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new LinkType();
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertInternalType('string', $this->type->getParent());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');

        $resolver
            ->expects($this->once())
            ->method('setRequired')
            ->with($this->isType('array'))
            ->will($this->returnSelf());

        $resolver
            ->expects($this->once())
            ->method('setOptional')
            ->with($this->isType('array'))
            ->will($this->returnSelf());

        $resolver
            ->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->will($this->returnSelf());

        $resolver
            ->expects($this->once())
            ->method('setAllowedTypes')
            ->with($this->isType('array'))
            ->will($this->returnSelf());

        $this->type->setDefaultOptions($resolver);
    }

    /**
     * @param array $options
     * @param array $expected
     * @dataProvider optionsProvider
     */
    public function testFinishView(array $options, array $expected)
    {
        $formView = $this->getMock('Symfony\Component\Form\FormView');
        $form     = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type->finishView($formView, $form, $options);
        $this->assertEquals($expected, $formView->vars);
    }

    /**
     * @return array
     */
    public function optionsProvider()
    {
        return [
            [
                [
                    'route'           => 'route',
                    'acl'             => 'acl',
                    'title'           => 'title',
                    'routeParameters' => [],
                    'isPath'          => false,
                    'class'           => ''
                ],
                [
                    'value'           => null,
                    'attr'            => [],
                    'route'           => 'route',
                    'acl'             => 'acl',
                    'title'           => 'title',
                    'routeParameters' => [],
                    'isPath'          => false,
                    'class'           => ''
                ]
            ]
        ];
    }
}
