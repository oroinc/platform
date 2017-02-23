<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Model\Update;
use Oro\Bundle\FormBundle\Model\UpdateBuilder;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Oro\Bundle\UIBundle\Route\Router;

class UpdateHandlerFacadeTest extends \PHPUnit_Framework_TestCase
{
    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    private $requestStack;

    /** @var Session|\PHPUnit_Framework_MockObject_MockObject */
    private $session;

    /** @var Router|\PHPUnit_Framework_MockObject_MockObject */
    private $router;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var UpdateBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $updateBuilder;

    /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $form;

    /** @var object */
    private $data;

    /** @var FormHandlerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $handler;

    /** @var FormTemplateDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $resultDataProvider;

    /** @var UpdateHandlerFacade */
    private $facade;

    protected function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->session = $this->createMock(Session::class);
        $this->router = $this->createMock(Router::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->updateBuilder = $this->createMock(UpdateBuilder::class);

        $this->form = $this->createMock(FormInterface::class);
        $this->data = (object)[];

        $this->handler = $this->createMock(FormHandlerInterface::class);
        $this->resultDataProvider = $this->createMock(FormTemplateDataProviderInterface::class);

        $this->facade = new UpdateHandlerFacade(
            $this->requestStack,
            $this->session,
            $this->router,
            $this->doctrineHelper,
            $this->updateBuilder
        );
    }

    protected function tearDown()
    {
        unset(
            $this->requestStack,
            $this->session,
            $this->router,
            $this->doctrineHelper,
            $this->updateBuilder,
            $this->form,
            $this->data,
            $this->facade
        );
    }

    public function testUpdateHandledWithoutWidget()
    {
        $this->defaultUpdateBuilding();

        $request = $this->createMock(Request::class);
        $this->requestStack->expects($this->never())->method('getCurrentRequest');

        $this->handler->expects($this->once())
            ->method('process')->with($this->data, $this->form, $request)->willReturn(true);

        //goes to construct response
        //not a widget
        $request->expects($this->at(0))->method('get')->with('_wid')->willReturn(false);

        $this->addingFlashMessage('save message');

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
        $this->defaultUpdateBuilding();

        $request = $this->createMock(Request::class);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->handler->expects($this->once())
            ->method('process')->with($this->data, $this->form, $request)->willReturn(true);

        //goes to construct response
        //not a widget
        $request->expects($this->at(0))->method('get')->with('_wid')->willReturn(false);

        $this->addingFlashMessage('save message');

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

    public function testUpdateHandledBuilderArgsPassedWithoutChanges()
    {
        $update = new Update();
        $update->data = (object) [];
        $update->form = $this->createMock(FormInterface::class);
        $update->saveMessage = 'used from update builder';
        $update->handler = $this->handler;
        $update->resultDataProvider = $this->resultDataProvider;

        $this->updateBuilder->expects($this->once())
            ->method('create')
            ->with($this->data, $this->form, '', null, null)->willReturn($update);

        $request = $this->createMock(Request::class);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->handler->expects($this->once())
            ->method('process')->with($update->data, $update->form, $request)->willReturn(true);

        //goes to construct response
        //not a widget
        $request->expects($this->at(0))->method('get')->with('_wid')->willReturn(false);

        $this->addingFlashMessage('used from update builder');

        $redirectResponse = $this->createMock(RedirectResponse::class);
        $this->router->expects($this->once())->method('redirect')->with($update->data)->willReturn($redirectResponse);

        $result = $this->facade->update($this->data, $this->form, '');

        $this->assertSame($redirectResponse, $result);
    }

    public function testUpdateHandledWithWidget()
    {
        $this->defaultUpdateBuilding();

        $request = $this->createMock(Request::class);
        $this->requestStack->expects($this->never())->method('getCurrentRequest');

        $this->handler->expects($this->once())
            ->method('process')->with($this->data, $this->form, $request)->willReturn(true);

        //goes to constructResponse
        //is a widget
        $request->expects($this->exactly(2))->method('get')->with('_wid')->willReturn('widget_test');

        //goes to getResult
        $formView = $this->createMock(FormView::class);
        $dataProviderResult = ['form' => $formView];
        $this->resultDataProvider->expects($this->once())
            ->method('getData')
            ->with($this->data, $this->form, $request)
            ->willReturn($dataProviderResult);

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
        $this->defaultUpdateBuilding();

        $request = $this->createMock(Request::class);
        $this->requestStack->expects($this->never())->method('getCurrentRequest');

        $this->handler->expects($this->once())
            ->method('process')->with($this->data, $this->form, $request)->willReturn(false);

        //goes to getResult
        $formView = $this->createMock(FormView::class);
        $dataProviderResult = ['form' => $formView];
        $this->resultDataProvider->expects($this->once())
            ->method('getData')
            ->with($this->data, $this->form, $request)
            ->willReturn($dataProviderResult);

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
        $this->defaultUpdateBuilding();

        $request = $this->createMock(Request::class);
        $this->requestStack->expects($this->never())->method('getCurrentRequest');

        $this->handler->expects($this->once())
            ->method('process')->with($this->data, $this->form, $request)->willReturn(false);

        //goes to getResult
        $formView = $this->createMock(FormView::class);
        $customEntityFromProvider = (object)[];
        $dataProviderResult = ['form' => $formView, 'entity' => $customEntityFromProvider];
        $this->resultDataProvider->expects($this->once())
            ->method('getData')
            ->with($this->data, $this->form, $request)
            ->willReturn($dataProviderResult);

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

    protected function defaultUpdateBuilding()
    {
        $update = new Update();
        $update->data = $this->data;
        $update->form = $this->form;
        $update->saveMessage = 'save message';
        $update->handler = $this->handler;
        $update->resultDataProvider = $this->resultDataProvider;

        $this->updateBuilder->expects($this->once())
            ->method('create')
            ->with(
                $this->data,
                $this->form,
                'save message',
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
