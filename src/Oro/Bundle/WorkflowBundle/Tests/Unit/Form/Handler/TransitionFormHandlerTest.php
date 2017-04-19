<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Form\Handler\TransitionFormHandler;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;

class TransitionFormHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject */
    private $unitOfWork;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    private $entityManager;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var Request|\PHPUnit_Framework_MockObject_MockObject */
    private $request;

    /** @var TransitionFormHandler */
    private $formHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $this->unitOfWork = $this->getMockBuilder(UnitOfWork::class)->disableOriginalConstructor()->getMock();

        $this->entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->entityManager->expects($this->any())->method('getUnitOfWork')->willReturn($this->unitOfWork);

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->any())->method('getEntityManager')->willReturn($this->entityManager);

        $this->formHandler = new TransitionFormHandler($this->doctrineHelper);
    }

    /**
     * @param bool $result
     * @param bool $isMethod
     * @param bool $isValid
     *
     * @dataProvider formDataProvider
     */
    public function testProcessStartTransitionForm($result, $isMethod, $isValid = false)
    {
        $this->request->expects($this->once())->method('isMethod')
            ->with('POST')
            ->willReturn($isMethod);

        $form = $this->createTransitionForm($isMethod, $isValid);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $transition = $this->createMock(Transition::class);

        $this->assertSame($result, $this->formHandler->processStartTransitionForm(
            $form,
            $workflowItem,
            $transition,
            $this->request
        ));
    }

    /**
     * @return \Generator
     */
    public function formDataProvider()
    {
        yield 'testSubmitWhenMethodIsNotPost' => [
            'result' => false,
            'isMethod' => false,
        ];

        yield 'testSubmitWhenFormNotValid' => [
            'result' => false,
            'isMethod' => true,
            'isValid' => false,
        ];

        yield 'testSubmitWhenFormIsValid' => [
            'result' => true,
            'isMethod' => true,
            'isValid' => true,
        ];
    }

    /**
     * @param bool $isFlush
     * @param array $formAttributes
     * @param bool $isInIdentityMap
     * @param bool $isScheduled
     * @param array $formAttributes
     *
     * @dataProvider formAttributesDataProvider
     */
    public function testFlushAttribute($isFlush, array $formAttributes, $isInIdentityMap = true, $isScheduled = false)
    {
        $this->request->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        $this->unitOfWork->expects($this->any())->method('isInIdentityMap')->willReturn($isInIdentityMap);
        $this->unitOfWork->expects($this->any())->method('isScheduledForInsert')->willReturn($isScheduled);

        $this->doctrineHelper->expects($this->any())->method('isManageableEntity')->willReturn(true);

        $expected = $isFlush ? $this->once() : $this->never();
        $this->entityManager->expects(clone $expected)->method('persist');
        $this->entityManager->expects(clone $expected)->method('flush');

        $form = $this->createTransitionForm(true, true, $formAttributes);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())->method('getFormOptions')->willReturn([
            'attribute_fields' => $formAttributes
        ]);

        $this->assertTrue($this->formHandler->processStartTransitionForm(
            $form,
            $workflowItem,
            $transition,
            $this->request
        ));
    }

    /**
     * @return \Generator
     */
    public function formAttributesDataProvider()
    {
        yield 'testFlushWhenEmptyAttributes' => [
            'isFlush' => false,
            'formAttributes' => [],
        ];

        yield 'testFlushWhenAttributeInIdentityMap' => [
            'isFlush' => false,
            'formAttributes' => ['attribute' => new \stdClass()],
            'isInIdentityMap' => true,
        ];

        yield 'testFlushWhenAttributeIsNotObject' => [
            'isFlush' => false,
            'formAttributes' => ['attribute' => 'string'],
            'isInIdentityMap' => true,
        ];

        yield 'testFlushWhenAttributeNotInIdentityMap' => [
            'isFlush' => true,
            'formAttributes' => ['attribute' => new \stdClass()],
            'isInIdentityMap' => false
        ];

        yield 'testFlushWhenAttributeIsScheduledForInsert' => [
            'isFlush' => true,
            'formAttributes' => ['attribute' => new \stdClass()],
            'isInIdentityMap' => true,
            'isScheduled' => true,
        ];
    }

    /**
     * @param bool $submit
     * @param bool $isValid
     * @param array $attributes
     *
     * @return Form|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createTransitionForm($submit, $isValid = true, array $attributes = [])
    {
        $form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()->getMock();
        $form->expects($submit ? $this->once() : $this->never())
            ->method('submit');
        $form->expects($submit ? $this->once() : $this->never())
            ->method('isValid')->willReturn($isValid);
        $form->expects($isValid ? $this->atLeastOnce() : $this->never())
            ->method('getData')->willReturn(new WorkflowData($attributes));

        return $form;
    }
}
