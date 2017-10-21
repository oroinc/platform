<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Operation\Execution;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\ActionBundle\Form\Type\OperationExecutionType;
use Oro\Bundle\ActionBundle\Operation\Execution\FormProvider;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;

class FormProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
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
        $this->formType = new OperationExecutionType();
        $this->formFactory = $this->getMockBuilder(FormFactoryInterface::class)->getMock();

        $this->formProvider = new FormProvider($this->formFactory, $this->formType);
    }

    public function testGetTokenData()
    {
        $actionData = new ActionData([ActionData::OPERATION_TOKEN => 'test_key']);
        $operation = $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock();
        $form = $this->getMockBuilder(FormInterface::class)->getMock();
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
        $operation = $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock();
        $form = $this->getMockBuilder(FormInterface::class)->getMock();
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
