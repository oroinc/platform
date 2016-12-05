<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Extension;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Form\Handler\TransitionFormHandler;

class TransitionFormHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject */
    private $unitOfWorkMock;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject*/
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
        $requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request =  $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()->getMock();

        $requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->unitOfWorkMock = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()->getMock();

        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWorkMock);
        $this->entityManager->expects($this->any())
            ->method('isManageableEntity')->willReturn(true);

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->any())->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->formHandler = new TransitionFormHandler($requestStack, $this->doctrineHelper);
    }

    /**
     * @param bool $result
     * @param bool $isMethod
     * @param bool $isValid
     *
     * @dataProvider formDataProvider
     */
    public function testHandlerRequest($result, $isMethod, $isValid = false)
    {
        $this->request->expects($this->once())->method('isMethod')
            ->with('POST')
            ->willReturn($isMethod);

        $form = $this->createTransitionForm($isMethod, $isValid);

        $this->assertSame($result, $this->formHandler->handleTransitionForm($form, []));
    }

    /**
     * @return array
     */
    public function formDataProvider()
    {
        return [
            'testFormSubmitWhenMethodIsNotPost' => [
                'result' => false,
                'isMethod' => false,
            ],
            'testWhenFormNotValid' => [
                'result' => false,
                'isMethod' => true,
                'isValid' => false,
            ],
            'testWhenFormIsValid' => [
                'result' => true,
                'isMethod' => true,
                'isValid' => true,
            ]
        ];
    }

    /**
     * @param bool $isFlush
     * @param bool $isInIdentityMap
     * @param bool $isScheduled
     * @param array $formAttributes
     *
     * @dataProvider formAttributeDataProvider
     */
    public function testFlushAttribute($isFlush, array $formAttributes, $isInIdentityMap = true, $isScheduled = false)
    {
        $this->request->expects($this->once())->method('isMethod')
            ->with('POST')
            ->willReturn(true);

        $form = $this->createTransitionForm(true, true);
        $this->unitOfWorkMock->expects($this->any())->method('isInIdentityMap')
            ->willReturn($isInIdentityMap);
        $this->unitOfWorkMock->expects($this->any())->method('isScheduledForInsert')
            ->willReturn($isScheduled);
        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntity')->willReturn(true);

        $expected = $isFlush ? $this->once() : $this->never();
        $this->entityManager->expects(clone $expected)->method('persist');
        $this->entityManager->expects(clone $expected)->method('flush');
        $this->assertSame(true, $this->formHandler->handleTransitionForm($form, $formAttributes));
    }

    /**
     * @return array
     */
    public function formAttributeDataProvider()
    {
        yield [
            'isFlush' => false,
            'formAttributes' => [],
        ];

        yield [
            'isFlush' => false,
            'formAttributes' => ['attribute' => new \stdClass()],
            'isInIdentityMap' => true,
        ];

        yield [
            'isFlush' => false,
            'formAttributes' => ['attribute' => 'string'],
            'isInIdentityMap' => true,
        ];

        yield [
            'isFlush' => true,
            'formAttributes' => ['attribute' => new \stdClass()],
            'isInIdentityMap' => false
        ];

        yield [
            'isFlush' => true,
            'formAttributes' => ['attribute' => new \stdClass()],
            'isInIdentityMap' => true,
            'isScheduled' => true,
        ];
    }

    /**
     * @param bool $submit
     * @param bool $isValid
     *
     * @return Form|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createTransitionForm($submit, $isValid = true)
    {
        $form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()->getMock();
        $form->expects($submit ? $this->once() : $this->never())
            ->method('submit');
        $form->expects($submit ? $this->once() : $this->never())
            ->method('isValid')->willReturn($isValid);

        return $form;
    }
}
