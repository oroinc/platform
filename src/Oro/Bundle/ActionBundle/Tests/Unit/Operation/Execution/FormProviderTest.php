<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Operation\Execution;

use Oro\Bundle\ActionBundle\Form\Type\OperationExecutionType;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Operation\Execution\FormProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FormProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $formFactory;

    /** @var OperationExecutionType */
    protected $formType;

    /** @var FormProvider */
    protected $formProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = OperationExecutionType::class;
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->formProvider = new FormProvider($this->formFactory, $this->formType);
    }

    public function testGetTokenData()
    {
        $actionData = new ActionData([ActionData::OPERATION_TOKEN => 'test_key']);
        $operation = $this->createMock(Operation::class);
        $form = $this->createMock(FormInterface::class);
        $formView = new FormView();
        $tokenView = new FormView();

        $reflection = new \ReflectionProperty(FormView::class, 'children');
        $reflection->setAccessible(true);
        $reflection->setValue($formView, [FormProvider::CSRF_TOKEN_FIELD => $tokenView]);

        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $options = ['csrf_token_id' => '_test_key'];
        $this->formFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->formType, $operation, $options)
            ->willReturn($form);

        $result = $this->formProvider->createTokenData($operation, $actionData);
        $this->assertArrayHasKey(OperationExecutionType::NAME, $result);
    }

    public function testGetOperationExecutionForm()
    {
        $actionData = new ActionData([ActionData::OPERATION_TOKEN => 'test_key']);
        $operation = $this->createMock(Operation::class);
        $form = $this->createMock(FormInterface::class);
        $options = ['csrf_token_id' => '_test_key'];
        $this->formFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->formType, $operation, $options)
            ->willReturn($form);

        $this->formProvider->getOperationExecutionForm($operation, $actionData);
    }

    protected function tearDown()
    {
        unset(
            $this->formProvider,
            $this->formFactory,
            $this->formType
        );
    }
}
