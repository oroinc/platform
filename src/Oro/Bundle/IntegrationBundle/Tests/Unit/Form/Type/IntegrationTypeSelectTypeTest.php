<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Form\Type\IntegrationTypeSelectType;

class IntegrationTypeSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var  IntegrationTypeSelectType */
    protected $type;

    /** @var TypesRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
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

    public function testSetDefaultOptions()
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

        $this->type->setDefaultOptions($resolver);
        $result = $resolver->resolve([]);
        $choiceAttr = [];
        foreach ($result['choices'] as $choice => $label) {
            $choiceAttr[$choice] = call_user_func($result['choice_attr'], $choice);
        }
        unset($result['choice_attr']);
        $this->assertEquals(
            [
                'empty_value' => '',
                'choices'     => [
                    'testType1' => 'oro.type1.label',
                    'testType2' => 'oro.type2.label'
                ],
                'configs'     => [
                    'placeholder'             => 'oro.form.choose_value',
                    'result_template_twig'    => 'OroIntegrationBundle:Autocomplete:type/result.html.twig',
                    'selection_template_twig' => 'OroIntegrationBundle:Autocomplete:type/selection.html.twig',
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
        $choiceList = $this->getMockBuilder('Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList')
            ->disableOriginalConstructor()->getMock();
        $options    = ['configs' => [], 'choice_list' => $choiceList];

        $this->type->buildView($view, $form, $options);

        $this->assertSame('oro.integration.form.no_available_integrations', $view->vars['configs']['placeholder']);
    }

    public function testGetParent()
    {
        $this->assertEquals('genemu_jqueryselect2_choice', $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_integration_type_select', $this->type->getName());
    }
}
