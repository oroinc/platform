<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowReplacementSelectType;

class WorkflowReplacementSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowReplacementSelectType */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->formType = new WorkflowReplacementSelectType();
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_jqueryselect2_hidden', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(WorkflowReplacementSelectType::NAME, $this->formType->getName());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $optionsResolver */
        $optionsResolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $optionsResolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    $this->assertArrayHasKey('autocomplete_alias', $options);
                    $this->assertEquals('oro_workflow_replacement', $options['autocomplete_alias']);

                    $this->assertArrayHasKey('workflow', $options);
                    $this->assertNull($options['workflow']);

                    $this->assertArrayHasKey('configs', $options);
                    $this->assertEquals(
                        [
                            'multiple' => true,
                            'component' => 'workflow-replacement',
                            'placeholder' => 'oro.workflow.workflowdefinition.placeholder.select_replacement',
                        ],
                        $options['configs']
                    );

                    $this->assertArrayHasKey('label', $options);
                    $this->assertEquals('oro.workflow.workflowdefinition.entity_plural_label', $options['label']);
                }
            );

        $this->formType->configureOptions($optionsResolver);
    }


    /**
     * @dataProvider buildViewDataProvider
     *
     * @param string|null $workflow
     * @param int|null $expectedId
     */
    public function testBuildView($workflow, $expectedId)
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $formView = new FormView();

        $this->formType->buildView($formView, $form, ['workflow' => $workflow]);

        $this->assertArrayHasKey('configs', $formView->vars);
        $this->assertArrayHasKey('entityId', $formView->vars['configs']);
        $this->assertEquals($expectedId, $formView->vars['configs']['entityId']);
    }

    /**
     * @return array
     */
    public function buildViewDataProvider()
    {
        return [
            'without workflow' => [
                'workflow' => null,
                'expectedId' => null,
            ],
            'with workflow' => [
                'workflow' => 'definition1',
                'expectedId' => 'definition1',
            ],
        ];
    }
}
