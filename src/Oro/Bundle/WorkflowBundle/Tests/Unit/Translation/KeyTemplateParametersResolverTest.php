<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplateParametersResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class KeyTemplateParametersResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var KeyTemplateParametersResolver */
    private $resolver;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->resolver = new KeyTemplateParametersResolver($this->translator);
    }

    public function testResolveTemplateParametersWithoutParameters()
    {
        $this->translator->expects($this->never())
            ->method('trans');

        $this->assertEquals([], $this->resolver->resolveTemplateParameters([]));
    }

    public function testResolveTemplateParameters()
    {
        $domain = WorkflowTranslationHelper::TRANSLATION_DOMAIN;

        $this->translator->expects($this->exactly(5))
            ->method('trans')
            ->willReturnMap([
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
            ]);

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
