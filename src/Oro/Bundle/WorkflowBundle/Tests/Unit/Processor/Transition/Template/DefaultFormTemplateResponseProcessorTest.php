<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition\Template;

use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Processor\Context\TemplateResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitActionResultTypeInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\Template\DefaultFormTemplateResponseProcessor;
use Symfony\Component\HttpFoundation\Response;

class DefaultFormTemplateResponseProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Twig_Environment|\PHPUnit\Framework\MockObject\MockObject */
    protected $twig;

    /** @var DefaultFormTemplateResponseProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->twig = $this->createMock(\Twig_Environment::class);

        $this->processor = new DefaultFormTemplateResponseProcessor($this->twig);
    }

    /**
     * @dataProvider templateProvider
     *
     * @param string|null $dialogTemplate
     * @param string $expectedToRender
     */
    public function testRenderedResponseResult($dialogTemplate, $expectedToRender)
    {
        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())->method('getDialogTemplate')->willReturn($dialogTemplate);

        $context = new TransitionContext();
        $context->setSaved(false);
        $context->setResultType(new TemplateResultType());
        $context->setTransition($transition);
        $context->set('template_parameters', ['parameter1' => 'value1']);

        $this->twig->expects($this->once())
            ->method('render')
            ->with($expectedToRender, ['parameter1' => 'value1'])
            ->willReturn('content');

        $this->processor->process($context);

        $this->assertTrue($context->isProcessed());
        $this->assertInstanceOf(Response::class, $context->getResult());
        $this->assertEquals('content', $context->getResult()->getContent());
    }

    /**
     * @return \Generator
     */
    public function templateProvider()
    {
        yield 'dialogTemplateDefined' => [
            'dialog-template',
            'dialog-template'
        ];

        yield 'default template if not defined' => [
            null,
            DefaultFormTemplateResponseProcessor::DEFAULT_TRANSITION_TEMPLATE
        ];
    }

    public function testSkipUnsupportedResultTypeContextSaved()
    {
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())->method('isSaved')->willReturn(true);
        $context->expects($this->never())->method('getResultType');
        $context->expects($this->never())->method('getTransition');

        $this->processor->process($context);
    }

    public function testSkipUnsupportedResultTypeContextResultType()
    {
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())->method('isSaved')->willReturn(false);
        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $context->expects($this->never())->method('getTransition');

        $this->processor->process($context);
    }
}
