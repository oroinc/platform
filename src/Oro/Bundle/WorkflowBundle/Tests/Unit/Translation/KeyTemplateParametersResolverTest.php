<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\WorkflowBundle\Translation\KeyTemplateParametersResolver;

class KeyTemplateParametersResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var KeyTemplateParametersResolver */
    protected $resolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock(TranslatorInterface::class);
        $this->resolver = new KeyTemplateParametersResolver($this->translator);
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
        $this->translator->expects($this->never())->method('trans');

        $this->assertEquals([], $this->resolver->resolveTemplateParameters([]));
    }

    public function testResolveTemplateParameters()
    {
        $domain = KeyTemplateParametersResolver::TRANSLATION_DOMAIN;

        $this->translator->expects($this->exactly(5))
            ->method('trans')
            ->will($this->returnValueMap([
                ['oro.workflow.workflow1.label', [], $domain, null, 'Workflow Label1'],
                ['oro.workflow.workflow1.transition.transition1.label', [], $domain, null, 'Transition Label1'],
                [
                    'oro.workflow.workflow1.transition.transition1.attribute.attribute1.label',
                    [],
                    $domain,
                    null,
                    'Transition Attribute Label1'
                ],
                ['oro.workflow.workflow1.step.step1.label', [], $domain, null, 'Step Label1'],
                ['oro.workflow.workflow1.attribute.attribute1.label', [], $domain, null, 'Workflow Attribute Label1'],
            ]));

        $this->assertEquals(
            [
                '{{ workflow_label }}' => 'Workflow Label1',
                '{{ transition_label }}' => 'Transition Label1',
                '{{ step_label }}' => 'Step Label1',
                '{{ transition_attribute_label }}' => 'Transition Attribute Label1',
                '{{ workflow_attribute_label }}' => 'Workflow Attribute Label1',
            ],
            $this->resolver->resolveTemplateParameters([
                'workflow_name' => 'workflow1',
                'transition_name' => 'transition1',
                'step_name' => 'step1',
                'attribute_name' => 'attribute1',
            ])
        );
    }
}
