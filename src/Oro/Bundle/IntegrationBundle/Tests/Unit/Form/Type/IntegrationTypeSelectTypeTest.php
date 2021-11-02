<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\IntegrationBundle\Form\Type\IntegrationTypeSelectType;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegrationTypeSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var TypesRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var Packages|\PHPUnit\Framework\MockObject\MockObject */
    private $assetHelper;

    /** @var IntegrationTypeSelectType */
    private $type;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(TypesRegistry::class);
        $this->assetHelper = $this->createMock(Packages::class);

        $this->type = new IntegrationTypeSelectType($this->registry, $this->assetHelper);
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->registry->expects($this->once())
            ->method('getAvailableIntegrationTypesDetailedData')
            ->willReturn(
                [
                    'testType1' => ['label' => 'oro.type1.label', 'icon' => 'bundles/acmedemo/img/logo.png'],
                    'testType2' => ['label' => 'oro.type2.label'],
                ]
            );

        $this->assetHelper->expects($this->once())
            ->method('getUrl')
            ->willReturnArgument(0);

        $this->type->configureOptions($resolver);
        $result = $resolver->resolve([]);
        $choiceAttr = [];
        foreach ($result['choices'] as $choice) {
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
        $view = $this->createMock(FormView::class);
        $form = $this->createMock(Form::class);
        $options = ['configs' => [], 'choices' => []];

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
