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
        self::assertIsString($this->type->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setRequired')
            ->with(self::isType('array'))
            ->willReturnSelf();
        $resolver->expects(self::once())
            ->method('setDefined')
            ->with(self::isType('array'))
            ->willReturnSelf();
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(self::isType('array'))
            ->willReturnSelf();
        $resolver->expects(self::exactly(3))
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

        self::assertEquals($expected, $formView->vars);
    }

    public function optionsProvider(): array
    {
        return [
            [
                [
                    'route'           => 'route',
                    'acl'             => 'acl',
                    'title'           => 'title',
                    'routeParameters' => []
                ],
                [
                    'value'           => null,
                    'attr'            => [],
                    'route'           => 'route',
                    'acl'             => 'acl',
                    'title'           => 'title',
                    'routeParameters' => []
                ]
            ]
        ];
    }
}
