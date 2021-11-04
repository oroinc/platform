<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\LinkType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LinkTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var LinkType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new LinkType();
    }

    public function testGetParent()
    {
        $this->assertIsString($this->type->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with($this->isType('array'))
            ->willReturnSelf();
        $resolver->expects($this->once())
            ->method('setDefined')
            ->with($this->isType('array'))
            ->willReturnSelf();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnSelf();
        $resolver->expects($this->exactly(3))
            ->method('setAllowedTypes')
            ->willReturnSelf();

        $this->type->configureOptions($resolver);
    }

    /**
     * @dataProvider optionsProvider
     */
    public function testFinishView(array $options, array $expected)
    {
        $formView = $this->createMock(FormView::class);
        $form = $this->createMock(Form::class);

        $this->type->finishView($formView, $form, $options);
        $this->assertEquals($expected, $formView->vars);
    }

    public function optionsProvider(): array
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
