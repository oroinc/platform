<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowReplacementSelectType;
use Oro\Component\Testing\Unit\EntityTrait;

class WorkflowReplacementSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

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

                    $this->assertArrayHasKey('configs', $options);
                    $this->assertEquals(
                        [
                            'multiple' => true,
                            'component' => 'workflow-replacement',
                            'placeholder' => 'oro.workflow.workflowdefinition.placeholder.select_replacement',
                        ],
                        $options['configs']
                    );
                }
            );

        $this->formType->configureOptions($optionsResolver);
    }


    /**
     * @dataProvider buildViewDataProvider
     *
     * @param object|null $parentData
     * @param int|null $expectedParentId
     */
    public function testBuildView($parentData, $expectedParentId)
    {
        $parentForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $parentForm->expects($this->once())->method('getData')->willReturn($parentData);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('getParent')->willReturn($parentForm);

        $formView = new FormView();

        $this->formType->buildView($formView, $form, []);

        $this->assertArrayHasKey('configs', $formView->vars);
        $this->assertArrayHasKey('entityId', $formView->vars['configs']);
        $this->assertEquals($expectedParentId, $formView->vars['configs']['entityId']);
    }

    /**
     * @return array
     */
    public function buildViewDataProvider()
    {
        return [
            'without entity' => [
                'parentData' => null,
                'expectedParentId' => null,
            ],
            'with entity' => [
                'parentData' => $this->getEntity('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition', [
                    'name' => 'definition1',
                ]),
                'expectedParentId' => 'definition1',
            ],
        ];
    }
}
