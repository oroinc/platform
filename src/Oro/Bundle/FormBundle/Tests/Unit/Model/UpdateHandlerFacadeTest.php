<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Model\UpdateFactory;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\FormBundle\Model\UpdateInterface;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;

class UpdateHandlerFacadeTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var Router|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var UpdateFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $updateFactory;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var object */
    private $data;

    /** @var UpdateHandlerFacade */
    private $facade;

    protected function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->session = $this->createMock(Session::class);
        $this->router = $this->createMock(Router::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->updateFactory = $this->createMock(UpdateFactory::class);

        $this->form = $this->createMock(FormInterface::class);
        $this->data = (object)[];

        $this->facade = new UpdateHandlerFacade(
            $this->requestStack,
            $this->session,
            $this->router,
            $this->doctrineHelper,
            $this->updateFactory
        );
    }

    public function testUpdateHandledWithoutWidget()
    {
        $update = $this->defaultUpdateBuilding();

        $request = $this->createMock(Request::class);
        $this->requestStack->expects($this->never())->method('getCurrentRequest');

        $update->expects($this->once())
            ->method('handle')->with($request)->willReturn(true);

        //goes to construct response
        //not a widget
        $request->expects($this->at(0))->method('get')->with('_wid')->willReturn(false);

        $this->addingFlashMessage('save message');

        $update->expects($this->once())->method('getFormData')->willReturn($this->data);

        $redirectResponse = $this->createMock(RedirectResponse::class);
        $this->router->expects($this->once())->method('redirect')->with($this->data)->willReturn($redirectResponse);
        $result = $this->facade->update(
            $this->data,
            $this->form,
            'save message',
            $request,
            'some_handler',
            'some_result_provider'
        );

        $this->assertSame($redirectResponse, $result);
    }

    public function testUpdateHandledWithoutRequestArgument()
    {
        $update = $this->defaultUpdateBuilding();

        $request = $this->createMock(Request::class);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $update->expects($this->once())->method('handle')->with($request)->willReturn(true);

        //goes to construct response
        //not a widget
        $request->expects($this->at(0))->method('get')->with('_wid')->willReturn(false);

        $this->addingFlashMessage('save message');

        $update->expects($this->once())->method('getFormData')->willReturn($this->data);

        $redirectResponse = $this->createMock(RedirectResponse::class);
        $this->router->expects($this->once())->method('redirect')->with($this->data)->willReturn($redirectResponse);
        $result = $this->facade->update(
            $this->data,
            $this->form,
            'save message',
            null,
            'some_handler',
            'some_result_provider'
        );

        $this->assertSame($redirectResponse, $result);
    }

    public function testUpdateHandledWithWidget()
    {
        $update = $this->defaultUpdateBuilding();

        $request = $this->createMock(Request::class);
        $this->requestStack->expects($this->never())->method('getCurrentRequest');

        $update->expects($this->once())
            ->method('handle')->with($request)->willReturn(true);

        //goes to constructResponse
        //is a widget
        $request->expects($this->exactly(2))
            ->method('get')->with('_wid')->willReturn('widget_test');

        //goes to getResult
        $formView = $this->createMock(FormView::class);
        $dataProviderResult = ['form' => $formView];
        $update->expects($this->once())
            ->method('getTemplateData')->with($request)->willReturn($dataProviderResult);

        $update->expects($this->exactly(2))->method('getFormData')->willReturn($this->data);
        //in constructResponse
        //pasting saveId
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')->with($this->data)->willReturn(42);

        //execute
        $result = $this->facade->update(
            $this->data,
            $this->form,
            'save message',
            $request,
            'some_handler',
            'some_result_provider'
        );

        $this->assertSame(
            [
                'form' => $formView,
                'entity' => $this->data,
                'isWidgetContext' => true,
                'savedId' => 42
            ],
            $result
        );
    }

    public function testUpdateNotHandled()
    {
        $update = $this->defaultUpdateBuilding();

        $request = $this->createMock(Request::class);
        $this->requestStack->expects($this->never())->method('getCurrentRequest');

        $update->expects($this->once())
            ->method('handle')->with($request)->willReturn(false);

        $update->expects($this->once())->method('getFormData')->willReturn($this->data);

        //goes to getResult
        $formView = $this->createMock(FormView::class);
        $dataProviderResult = ['form' => $formView];
        $update->expects($this->once())
            ->method('getTemplateData')->with($request)->willReturn($dataProviderResult);

        //isWidgetContext => true
        $request->expects($this->once())->method('get')->with('_wid')->willReturn('widget_test');

        $result = $this->facade->update(
            $this->data,
            $this->form,
            'save message',
            $request,
            'some_handler',
            'some_result_provider'
        );

        $this->assertSame(['form' => $formView, 'entity' => $this->data, 'isWidgetContext' => true], $result);
    }

    public function testUpdateNotHandledEntityFromProviderNotOverride()
    {
        $update = $this->defaultUpdateBuilding();

        $request = $this->createMock(Request::class);
        $this->requestStack->expects($this->never())->method('getCurrentRequest');

        $update->expects($this->once())->method('handle')->with($request)->willReturn(false);

        //goes to getResult
        $formView = $this->createMock(FormView::class);
        $customEntityFromProvider = (object)[];
        $dataProviderResult = ['form' => $formView, 'entity' => $customEntityFromProvider];

        $update->expects($this->once())->method('getTemplateData')->with($request)->willReturn($dataProviderResult);

        //isWidgetContext => false
        $request->expects($this->once())->method('get')->with('_wid')->willReturn(false);

        $result = $this->facade->update(
            $this->data,
            $this->form,
            'save message',
            $request,
            'some_handler',
            'some_result_provider'
        );

        $this->assertSame(
            ['form' => $formView, 'entity' => $customEntityFromProvider, 'isWidgetContext' => false],
            $result
        );
    }

    /**
     * @return UpdateInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function defaultUpdateBuilding()
    {
        $update = $this->createMock(UpdateInterface::class);

        $this->updateFactory->expects($this->once())
            ->method('createUpdate')
            ->with(
                $this->data,
                $this->form,
                'some_handler',
                'some_result_provider'
            )->willReturn($update);

        return $update;
    }

    /**
     * @param string $message
     */
    protected function addingFlashMessage($message)
    {
        $flashBag = $this->createMock(FlashBag::class);
        $this->session->expects($this->once())->method('getFlashBag')->willReturn($flashBag);
        $flashBag->expects($this->once())->method('add')->with('success', $message);
    }
}
