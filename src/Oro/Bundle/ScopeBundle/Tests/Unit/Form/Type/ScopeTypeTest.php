<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ScopeBundle\Form\DataTransformer\ScopeTransformer;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScopeTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeManager;

    /**
     * @var ScopeType
     */
    protected $scopeType;

    protected function setUp()
    {
        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeType = new ScopeType($this->scopeManager);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver **/
        $resolver = $this->getMock(OptionsResolver::class);

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
                ScopeType::SCOPE_FIELDS_OPTION => []
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
     * @param array $options
     * @param array $children
     * @param array $expected
     */
    public function testFinishView(array $options, array $children, array $expected)
    {
        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = new FormView();

        $view->children = $children;

        /** @var \Symfony\Component\Form\FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeType->finishView($view, $form, $options);

        $this->assertEquals($expected, $view->children);
    }

    /**
     * @return array
     */
    public function finishViewDataProvider()
    {
        return [
            [
                'options' => [
                    ScopeType::SCOPE_FIELDS_OPTION => [
                        'one' => '\stdClass',
                        'two' => '\stdClass',
                        'three' => '\stdClass'
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

        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->with($scopeTransformer);

        $this->scopeType->buildForm($builder, [ScopeType::SCOPE_TYPE_OPTION => $scopeType]);
    }

    public function testGetName()
    {
        $this->assertEquals(ScopeType::NAME, $this->scopeType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ScopeType::NAME, $this->scopeType->getBlockPrefix());
    }
}
