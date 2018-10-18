<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\LinkType;

class LinkTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LinkType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new LinkType();
    }

    public function testGetParent()
    {
        $this->assertInternalType('string', $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');

        $resolver
            ->expects($this->once())
            ->method('setRequired')
            ->with($this->isType('array'))
            ->will($this->returnSelf());

        $resolver
            ->expects($this->once())
            ->method('setDefined')
            ->with($this->isType('array'))
            ->will($this->returnSelf());

        $resolver
            ->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->will($this->returnSelf());

        $resolver
            ->expects($this->exactly(3))
            ->method('setAllowedTypes')
            ->will($this->returnSelf());

        $this->type->configureOptions($resolver);
    }

    /**
     * @param array $options
     * @param array $expected
     * @dataProvider optionsProvider
     */
    public function testFinishView(array $options, array $expected)
    {
        $formView = $this->createMock('Symfony\Component\Form\FormView');
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
