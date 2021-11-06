<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ScopeBundle\Form\DataTransformer\ScopeTransformer;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeType;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScopeTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeManager;

    /** @var ScopeType */
    private $scopeType;

    protected function setUp(): void
    {
        $this->scopeManager = $this->createMock(ScopeManager::class);

        $this->scopeType = new ScopeType($this->scopeManager);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setRequired')
            ->with([
                ScopeType::SCOPE_TYPE_OPTION,
            ]);

        $resolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with(ScopeType::SCOPE_TYPE_OPTION, ['string']);

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                ScopeType::SCOPE_FIELDS_OPTION => [],
                'error_bubbling' => false
            ]);

        $resolver->expects($this->once())
            ->method('setNormalizer')
            ->with(
                ScopeType::SCOPE_FIELDS_OPTION,
                function (Options $options) {
                    return $this->scopeManager->getScopeEntities($options[ScopeType::SCOPE_TYPE_OPTION]);
                }
            );

        $this->scopeType->configureOptions($resolver);
    }

    /**
     * @dataProvider finishViewDataProvider
     */
    public function testFinishView(array $options, array $children, array $expected)
    {
        $view = new FormView();
        $view->children = $children;

        $form = $this->createMock(FormInterface::class);

        $this->scopeType->finishView($view, $form, $options);

        $this->assertEquals($expected, $view->children);
    }

    public function finishViewDataProvider(): array
    {
        return [
            [
                'options' => [
                    ScopeType::SCOPE_FIELDS_OPTION => [
                        'one' => \stdClass::class,
                        'two' => \stdClass::class,
                        'three' => \stdClass::class
                    ]
                ],
                'children' => [
                    'two' => new FormView(),
                    'one' => new FormView(),
                    'three' => new FormView()
                ],
                'expected' => [
                    'three' => new FormView(),
                    'two' => new FormView(),
                    'one' => new FormView()
                ]
            ]
        ];
    }

    public function testBuildForm()
    {
        $scopeType = 'test_scope_type';
        $scopeTransformer = new ScopeTransformer($this->scopeManager, $scopeType);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->with($scopeTransformer);

        $this->scopeType->buildForm($builder, [ScopeType::SCOPE_TYPE_OPTION => $scopeType]);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ScopeType::NAME, $this->scopeType->getBlockPrefix());
    }
}
