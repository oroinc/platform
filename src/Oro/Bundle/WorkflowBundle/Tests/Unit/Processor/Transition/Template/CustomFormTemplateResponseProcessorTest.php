<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition\Template;

use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Processor\Context\TemplateResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitActionResultTypeInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\Template\CustomFormTemplateResponseProcessor;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Suppressing for stubs and mock classes
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomFormTemplateResponseProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Twig_Environment|\PHPUnit\Framework\MockObject\MockObject */
    protected $twig;

    /** @var FormTemplateDataProviderRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $templateDataProviderRegistry;

    /** @var CustomFormTemplateResponseProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->twig = $this->createMock(\Twig_Environment::class);
        $this->templateDataProviderRegistry = $this->createMock(FormTemplateDataProviderRegistry::class);

        $this->processor = new CustomFormTemplateResponseProcessor(
            $this->twig,
            $this->templateDataProviderRegistry
        );
    }

    public function testRenderedResponseResult()
    {
        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())->method('getDialogTemplate')->willReturn(null);
        $transition->expects($this->once())->method('getFormDataProvider')->willReturn('provider_name');
        $transition->expects($this->once())->method('getFormDataAttribute')->willReturn('entity');

        $entity = (object)['id' => 42];

        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');
        $workflowItem->expects($this->once())->method('getData')->willReturn(new WorkflowData(['entity' => $entity]));

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);

        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(Request::class);

        $context = new TransitionContext();
        $context->setSaved(false);
        $context->setResultType(new TemplateResultType());
        $context->setTransition($transition);
        $context->setWorkflowItem($workflowItem);
        $context->setIsCustomForm(true);
        $context->setForm($form);
        $context->setRequest($request);
        $context->set('template_parameters', ['p1' => 'v1', 'p2' => 'v2']);

        /** @var FormTemplateDataProviderInterface|\PHPUnit\Framework\MockObject\MockObject $dataProvider */
        $dataProvider = $this->createMock(FormTemplateDataProviderInterface::class);
        $dataProvider->expects($this->once())
            ->method('getData')
            ->with($entity, $form, $request)
            ->willReturn(['data' => 'value', 'p1' => 'context template data should override']);

        $this->templateDataProviderRegistry->expects($this->once())
            ->method('get')
            ->with('provider_name')
            ->willReturn($dataProvider);

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                CustomFormTemplateResponseProcessor::DEFAULT_TRANSITION_CUSTOM_FORM_TEMPLATE,
                ['data' => 'value', 'p1' => 'v1', 'p2' => 'v2']
            )->willReturn('content');

        $this->processor->process($context);

        $this->assertTrue($context->isProcessed());
        $this->assertInstanceOf(Response::class, $context->getResult());
        $this->assertEquals('content', $context->getResult()->getContent());
    }

    public function testSkipNonCustomFormContext()
    {
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);

        $context->expects($this->once())
            ->method('isSaved')->willReturn(false);

        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn(new TemplateResultType());

        $context->expects($this->once())
            ->method('isCustomForm')
            ->willReturn(false);

        $context->expects($this->never())->method('getTransition');

        $this->processor->process($context);
    }

    public function testSkipUnsupportedResultType()
    {
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);

        $context->expects($this->once())
            ->method('isSaved')->willReturn(false);

        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $context->expects($this->never())->method('getTransition');

        $this->processor->process($context);
    }

    public function testSkipNotSaved()
    {
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);

        $context->expects($this->once())
            ->method('isSaved')->willReturn(true);

        $context->expects($this->never())->method('getTransition');

        $this->processor->process($context);
    }
}
