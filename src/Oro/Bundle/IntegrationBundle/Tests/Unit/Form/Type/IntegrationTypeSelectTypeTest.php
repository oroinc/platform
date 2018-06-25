<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\IntegrationBundle\Form\Type\IntegrationTypeSelectType;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegrationTypeSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var  IntegrationTypeSelectType */
    protected $type;

    /** @var TypesRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var  \PHPUnit\Framework\MockObject\MockObject */
    protected $assetHelper;

    protected function setUp()
    {
        $this->registry    = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Manager\TypesRegistry')
            ->disableOriginalConstructor()->getMock();
        $this->assetHelper = $this->getMockBuilder('Symfony\Component\Asset\Packages')
            ->disableOriginalConstructor()->getMock();
        $this->type        = new IntegrationTypeSelectType($this->registry, $this->assetHelper);
    }

    public function tearDown()
    {
        unset($this->type, $this->registry, $this->assetHelper);
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->registry->expects($this->once())->method('getAvailableIntegrationTypesDetailedData')
            ->will(
                $this->returnValue(
                    [
                        'testType1' => ['label' => 'oro.type1.label', 'icon' => 'bundles/acmedemo/img/logo.png'],
                        'testType2' => ['label' => 'oro.type2.label'],
                    ]
                )
            );

        $this->assetHelper->expects($this->once())
            ->method('getUrl')
            ->will($this->returnArgument(0));

        $this->type->configureOptions($resolver);
        $result = $resolver->resolve([]);
        $choiceAttr = [];
        foreach ($result['choices'] as $label => $choice) {
            $choiceAttr[$choice] = call_user_func($result['choice_attr'], $choice);
        }
        unset($result['choice_attr']);
        $this->assertEquals(
            [
                'choices'     => [
                    'oro.type1.label' => 'testType1',
                    'oro.type2.label' => 'testType2'
                ],
                'configs'     => [
                    'placeholder' => 'oro.form.choose_value',
                    'showIcon'    => true,
                ]
            ],
            $result
        );
        $this->assertEquals(
            [
                'testType1' => ['data-icon' => 'bundles/acmedemo/img/logo.png'],
                'testType2' => []
            ],
            $choiceAttr
        );
    }

    public function testBuildView()
    {
        $view       = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()->getMock();
        $form       = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()->getMock();
        $options    = ['configs' => [], 'choices' => []];

        $this->type->buildView($view, $form, $options);

        $this->assertSame('oro.integration.form.no_available_integrations', $view->vars['configs']['placeholder']);
    }

    public function testGetParent()
    {
        $this->assertEquals(Select2ChoiceType::class, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_integration_type_select', $this->type->getName());
    }
}
