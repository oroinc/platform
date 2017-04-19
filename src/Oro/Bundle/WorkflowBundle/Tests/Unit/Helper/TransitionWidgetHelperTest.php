<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Helper\TransitionWidgetHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowDataSerializer;
use Oro\Component\Action\Action\ActionInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class TransitionWidgetHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var WorkflowDataSerializer|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowDataSerializer;

    /** @var TransitionWidgetHelper */
    protected $helper;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->workflowDataSerializer = $this->createMock(WorkflowAwareSerializer::class);
        $this->helper = new TransitionWidgetHelper(
            $this->doctrineHelper,
            $this->formFactory,
            $this->workflowDataSerializer
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset(
            $this->doctrineHelper,
            $this->formFactory,
            $this->workflowDataSerializer,
            $this->helper
        );
    }

    /**
     * @param string $entityClass
     * @param null|mixed $entityId
     *
     * @dataProvider getOrCreateEntityReferenceDataProvider
     */
    public function testGetOrCreateEntityReference($entityClass, $entityId = null)
    {
        if ($entityId) {
            $this->doctrineHelper->expects($this->once())->method('getEntityReference')->with($entityClass, $entityId);
        } else {
            $this->doctrineHelper->expects($this->once())->method('createEntityInstance')->with($entityClass);
        }

        $this->helper->getOrCreateEntityReference($entityClass, $entityId);
    }

    /**
     * @param string $entityClass
     * @param null|mixed $entityId
     *
     * @dataProvider getOrCreateEntityReferenceDataProvider
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testGetOrCreateEntityReferenceException($entityClass, $entityId = null)
    {
        if ($entityId) {
            $this->doctrineHelper->expects($this->once())
                ->method('getEntityReference')
                ->with($entityClass, $entityId)
                ->willThrowException(new NotManageableEntityException('message'));
        } else {
            $this->doctrineHelper->expects($this->once())
                ->method('createEntityInstance')
                ->with($entityClass)
                ->willThrowException(new NotManageableEntityException('message'));
        }

        $this->helper->getOrCreateEntityReference($entityClass, $entityId);
    }

    /**
     * @return \Generator
     */
    public function getOrCreateEntityReferenceDataProvider()
    {
        yield 'with id' => ['entityClass' => 'SomeClass', 'entityId' => 1];
        yield 'without id' => ['entityClass' => 'SomeClass'];
    }

    public function testGetEntityManager()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with('OroWorkflowBundle:WorkflowItem');
        $this->helper->getEntityManager();
    }

    /**
     * @param bool $throwException
     *
     * @dataProvider getTransitionFormDataProvider
     */
    public function testGetTransitionFormNative($throwException)
    {
        $formType = 'formType';
        $formData = 'formData';
        $formOptions = [];
        $transitionName = 'transitionName';
        /** @var WorkflowItem|\PHPUnit_Framework_MockObject_MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);

        /** @var Transition|\PHPUnit_Framework_MockObject_MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->any())->method('getFormType')->willReturn($formType);
        $transition->expects($this->any())->method('hasFormConfiguration')->willReturn(false);
        $transition->expects($this->any())->method('getName')->willReturn($transitionName);
        $transition->expects($this->any())->method('getFormOptions')->willReturn($formOptions);

        if ($throwException) {
            $this->expectException('\Symfony\Component\HttpKernel\Exception\BadRequestHttpException');
            $this->formFactory->expects($this->never())->method('create');
            $formData = null;
        } else {
            $this->formFactory->expects($this->once())->method('create')->with(
                $formType,
                $formData,
                array_merge($formOptions, [
                    'workflow_item' => $workflowItem,
                    'transition_name' => $transitionName
                ])
            );
        }

        $workflowItem->expects($this->atLeastOnce())->method('getData')->willReturn($formData);
        $this->helper->getTransitionForm($workflowItem, $transition);
    }

    /**
     * @param bool $throwException
     *
     * @dataProvider getTransitionFormDataProvider
     */
    public function testGetTransitionFormCustom($throwException)
    {
        $formType = 'formType';
        $formData = 'formData';
        $pageFormDataAttribute = 'formDataAttribute';
        $action = $this->getMockBuilder(ActionInterface::class)->getMockForAbstractClass();
        $formOptions = ['form_init' => $action];

        /** @var WorkflowData|\PHPUnit_Framework_MockObject_MockObject $workflowData */
        $workflowData = $this->getMockBuilder(WorkflowData::class)->disableOriginalConstructor()->getMock();

        /** @var WorkflowItem|\PHPUnit_Framework_MockObject_MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $action->expects($this->any())->method('execute')->with($workflowItem);

        /** @var Transition|\PHPUnit_Framework_MockObject_MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->any())->method('getFormType')->willReturn($formType);
        $transition->expects($this->any())->method('hasFormConfiguration')->willReturn(true);
        $transition->expects($this->any())->method('getFormDataAttribute')->willReturn($pageFormDataAttribute);
        $transition->expects($this->any())->method('getFormOptions')->willReturn($formOptions);

        if ($throwException) {
            $this->expectException('\Symfony\Component\HttpKernel\Exception\BadRequestHttpException');
            $this->formFactory->expects($this->never())->method('create');
            $formData = null;
        } else {
            $this->formFactory->expects($this->once())->method('create')->with($formType, $formData, []);
        }

        $workflowData->expects($this->atLeastOnce())->method('get')->with()->willReturn($formData);
        $workflowItem->expects($this->atLeastOnce())->method('getData')->willReturn($workflowData);

        $this->helper->getTransitionForm($workflowItem, $transition);
    }

    /**
     * @return \Generator
     */
    public function getTransitionFormDataProvider()
    {
        yield 'with exception' => ['throwException' => true];
        yield 'without exception' => ['throwException' => false];
    }

    /**
     * @param string $expected
     * @param bool $hasPageConfiguration
     * @param null|string $dialogTemplate
     *
     * @dataProvider getTransitionFormTemplateDataProvider
     */
    public function testGetTransitionFormTemplate($expected, $hasPageConfiguration, $dialogTemplate = null)
    {
        /** @var Transition|\PHPUnit_Framework_MockObject_MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())->method('hasFormConfiguration')->willReturn($hasPageConfiguration);
        $transition->expects($this->once())->method('getDialogTemplate')->willReturn($dialogTemplate);

        $this->assertEquals($expected, $this->helper->getTransitionFormTemplate($transition));
    }

    /**
     * @return \Generator
     */
    public function getTransitionFormTemplateDataProvider()
    {
        yield 'has configuration and not template' => [
            TransitionWidgetHelper::DEFAULT_TRANSITION_CUSTOM_FORM_TEMPLATE,
            true,
            null
        ];
        yield 'has configuration and template' => [
            'template',
            true,
            'template'
        ];
        yield 'has no configuration and not template' => [
            TransitionWidgetHelper::DEFAULT_TRANSITION_TEMPLATE,
            false,
            null
        ];
        yield 'has configuration and not template' => [
            'template',
            false,
            'template'
        ];
    }

    /**
     * @param bool $hasPageConfiguration
     *
     * @dataProvider processWorkflowDataDataProvider
     */
    public function testProcessWorkflowData($hasPageConfiguration)
    {
        /** @var Workflow|\PHPUnit_Framework_MockObject_MockObject $transition $workflow */
        $workflow = $this->createMock(Workflow::class);
        $workflowName = 'workflowName';
        $workflow->expects($this->once())->method('getName')->willReturn($workflowName);

        /** @var Transition|\PHPUnit_Framework_MockObject_MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())->method('hasFormConfiguration')->willReturn($hasPageConfiguration);

        $transitionFormData = $this->createMock(WorkflowData::class);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $transitionForm */
        $transitionForm = $this->getMockBuilder(FormInterface::class)->getMockForAbstractClass();
        $transitionForm->expects($this->once())->method('getData')->willReturn($transitionFormData);

        if ($hasPageConfiguration) {
            $pageFormDataAttribute = 'formDataAttribute';
            $transition->expects($this->once())->method('getFormDataAttribute')->willReturn($pageFormDataAttribute);
        } else {
            $attributeFields = ['one' => 1, 'two' => 2];
            $transition->expects($this->once())
                ->method('getFormOptions')
                ->willReturn(['attribute_fields' => $attributeFields]);
            $transitionFormData->expects($this->once())->method('getValues')
                ->with(array_keys($attributeFields))
                ->willReturn(array_values($attributeFields));
        }

        $this->workflowDataSerializer->expects($this->once())->method('setWorkflowName')->with($workflowName);
        $this->workflowDataSerializer->expects($this->once())->method('serialize')->withAnyParameters();

        $this->helper->processWorkflowData($workflow, $transition, $transitionForm);
    }

    /**
     * @return \Generator
     */
    public function processWorkflowDataDataProvider()
    {
        yield 'with page configuration' => ['hasPageConfiguration' => true];
        yield 'without page configuration' => ['hasPageConfiguration' => true];
    }
}
