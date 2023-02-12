<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Context;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TransitionContextTest extends \PHPUnit\Framework\TestCase
{
    private TransitionContext $context;

    protected function setUp(): void
    {
        $this->context = new TransitionContext();
    }

    /**
     * @dataProvider constantMatch
     */
    public function testConstantsUsedForAttributeMatching(string $constantValue, string $expectedValue)
    {
        $this->assertSame($expectedValue, $constantValue);
    }

    public function constantMatch(): array
    {
        return [
            [TransitionContext::CUSTOM_FORM, 'customForm'],
            [TransitionContext::IS_START, 'isStart'],
            [TransitionContext::HAS_INIT_OPTIONS, 'hasInitOptions'],
            [TransitionContext::SAVED, 'saved'],
            [TransitionContext::STATE, 'state'],
            [TransitionContext::STATE_OK, 'ok'],
            [TransitionContext::STATE_FAILURE, 'failure'],
            [TransitionContext::INIT_DATA, 'initData'],
            [TransitionContext::RESULT_TYPE, 'resultType'],
            [TransitionContext::PROCESSED, 'processed'],
        ];
    }

    public function testDefaults()
    {
        $this->assertDefaultValues();
    }

    public function testClear()
    {
        $this->context->set(TransitionContext::IS_START, true);
        $this->context->set(TransitionContext::SAVED, true);
        $this->context->set(TransitionContext::PROCESSED, true);
        $this->context->set(TransitionContext::STATE, TransitionContext::STATE_FAILURE);

        $this->context->clear();

        $this->assertDefaultValues();
    }

    private function assertDefaultValues()
    {
        $this->assertFalse($this->context->get(TransitionContext::IS_START));
        $this->assertFalse($this->context->get(TransitionContext::SAVED));
        $this->assertFalse($this->context->get(TransitionContext::PROCESSED));
        $this->assertEquals(TransitionContext::STATE_OK, $this->context->get(TransitionContext::STATE));
    }

    /**
     * @dataProvider accessorsStorageHelpers
     */
    public function testAccessorStores(string $setAccessor, string $getAccessor, string $storageProperty, mixed $value)
    {
        $this->context->{$setAccessor}($value);
        $this->assertSame($value, $this->context->{$getAccessor}());
        $this->assertSame($value, $this->context->get($storageProperty));
    }

    public function accessorsStorageHelpers(): array
    {
        return [
            ['setTransitionName', 'getTransitionName', TransitionContext::TRANSITION_NAME, 't1'],
            ['setWorkflowName', 'getWorkflowName', TransitionContext::WORKFLOW_NAME, 'w1'],
            ['setIsStartTransition', 'isStartTransition', TransitionContext::IS_START, true],
            ['setSaved', 'isSaved', TransitionContext::SAVED, true],
            ['setProcessed', 'isProcessed', TransitionContext::PROCESSED, true],
            ['setIsCustomForm', 'isCustomForm', TransitionContext::CUSTOM_FORM, true]
        ];
    }

    public function testResultTypeStoredAsString()
    {
        $this->context->setResultType(new ResultTypeStub('result type'));
        $this->assertSame('result type', $this->context->get(TransitionContext::RESULT_TYPE));
    }

    public function testError()
    {
        $this->assertFalse($this->context->hasError());
        $this->assertEquals(TransitionContext::STATE_OK, $this->context->get(TransitionContext::STATE));

        $error = new \Exception;
        $this->context->setError($error);

        $this->assertEquals(TransitionContext::STATE_FAILURE, $this->context->get(TransitionContext::STATE));
        $this->assertSame(
            $error,
            $this->context->getError(),
            'Error object should not be modified internally'
        );
        $this->assertTrue($this->context->hasError());
    }

    /**
     * @dataProvider accessorsProperties
     */
    public function testContextOwnPropertiesAccessors(string $setAccessor, string $getAccessor, mixed $value)
    {
        $this->context->{$setAccessor}($value);
        $this->assertSame($value, $this->context->{$getAccessor}());
    }

    public function accessorsProperties(): array
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');

        return [
            ['setTransition', 'getTransition', $this->createMock(Transition::class)],
            ['setWorkflow', 'getWorkflow', $this->createMock(Workflow::class)],
            ['setWorkflowItem', 'getWorkflowItem', $workflowItem],
            ['setRequest', 'getRequest', $this->createMock(Request::class)],
            ['setForm', 'getForm', $this->createMock(FormInterface::class)],
            ['setFormData', 'getFormData', (object)['id' => 42]],
            ['setFormOptions', 'getFormOptions', ['option1' => 'val1']],
            ['setError', 'getError', new \Exception]
        ];
    }

    /**
     * Strict prevent from unexpected value retrieval in processors from badly configured context
     * @dataProvider strictAccessors
     */
    public function testStrictGetters(string $accessor, ?string $storageKey = null, ?string $storageValue = null)
    {
        if ($storageKey !== null) {
            $this->context->set($storageKey, $storageValue);
        }

        $this->expectException(\Error::class);

        $this->context->{$accessor}();
    }

    public function strictAccessors(): array
    {
        return [
            ['getTransitionName'],
            ['getWorkflowName'],
            ['getTransition'],
            ['getWorkflow'],
            ['getWorkflowItem'],
            ['getRequest'],
            ['getResponseType'],
            ['getForm'],
            ['getError']
        ];
    }

    /**
     * @dataProvider hasAccessors
     */
    public function testHas(string $setter, string $hasAccessor, mixed $value)
    {
        $this->assertFalse($this->context->{$hasAccessor}());
        $this->context->{$setter}($value);
        $this->assertTrue($this->context->{$hasAccessor}());
    }

    public function hasAccessors(): array
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');

        return [
            ['setWorkflowItem', 'hasWorkflowItem', $workflowItem],
            ['setError', 'hasError', $this->createMock(\Error::class)]
        ];
    }
}
