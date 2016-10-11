<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplateParametersResolver;

class KeyTemplateParametersResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var TransitionManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $transitionManager;

    /** @var Workflow|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflow;

    /** @var KeyTemplateParametersResolver */
    protected $resolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock(TranslatorInterface::class);
        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transitionManager = $this->getMockBuilder(TransitionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflow->expects($this->any())
            ->method('getTransitionManager')
            ->willReturn($this->transitionManager);

        $this->resolver = new KeyTemplateParametersResolver($this->translator, $this->workflowManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->translator, $this->workflowManager, $this->transitionManager, $this->workflow, $this->resolver);
    }

    public function testResolveTemplateParametersWithoutParameters()
    {
        $this->workflowManager->expects($this->never())->method('getWorkflow');
        $this->translator->expects($this->never())->method('trans');

        $this->assertEquals([], $this->resolver->resolveTemplateParameters([]));
    }

    public function testResolveTemplateParameters()
    {
        $domain = KeyTemplateParametersResolver::TRANSLATION_DOMAIN;

        $this->workflowManager->expects($this->never())->method('getWorkflow');
        $this->translator->expects($this->exactly(3))
            ->method('trans')
            ->will($this->returnValueMap([
                ['oro.workflow.workflow1.label', [], $domain, null, 'Workflow Label1'],
                ['oro.workflow.workflow1.transition.transition1.label', [], $domain, null, 'Transition Label1'],
                ['oro.workflow.workflow1.step.step1.label', [], $domain, null, 'Step Label1'],
            ]));

        $this->assertEquals(
            [
                '{{ workflow_label }}' => 'Workflow Label1',
                '{{ transition_label }}' => 'Transition Label1',
                '{{ step_label }}' => 'Step Label1',
            ],
            $this->resolver->resolveTemplateParameters([
                'workflow_name' => 'workflow1',
                'transition_name' => 'transition1',
                'step_name' => 'step1',
            ])
        );
    }

    public function testResolveTemplateParametersWithAttribute()
    {
        $domain = KeyTemplateParametersResolver::TRANSLATION_DOMAIN;

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with('workflow1')
            ->willReturn($this->workflow);

        $this->transitionManager->expects($this->once())
            ->method('getTransitions')
            ->willReturn([
                (new Transition())
                    ->setName('transition1')
                    ->setFormOptions([
                        'attribute_fields' => [
                            'attribute1' => [],
                        ],
                    ]),
            ]);

        $this->translator->expects($this->exactly(3))
            ->method('trans')
            ->will($this->returnValueMap([
                ['oro.workflow.workflow1.label', [], $domain, null, 'Workflow Label1'],
                ['oro.workflow.workflow1.transition.transition1.label', [], $domain, null, 'Transition Label1'],
                ['oro.workflow.workflow1.attribute.attribute1.label', [], $domain, null, 'Attribute Label1'],
            ]));

        $this->assertEquals(
            [
                '{{ workflow_label }}' => 'Workflow Label1',
                '{{ transition_label }}' => 'Transition Label1',
                '{{ attribute_label }}' => 'Attribute Label1',
            ],
            $this->resolver->resolveTemplateParameters([
                'workflow_name' => 'workflow1',
                'transition_name' => 'transition1',
                'attribute_name' => 'attribute1',
            ])
        );
    }
}
