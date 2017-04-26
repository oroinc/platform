<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler\Helper;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Handler\Helper\TransitionHelper;

class TransitionHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var ViewHandlerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $viewHandler;

    /** @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject */
    protected $twig;

    /** @var TransitionHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->viewHandler = $this->createMock(ViewHandlerInterface::class);
        $this->twig = $this->createMock(\Twig_Environment::class);

        $this->helper = new TransitionHelper($this->viewHandler, $this->twig);
    }

    public function testCreateCompleteResponseWith200Code()
    {
        $this->viewHandler->expects($this->never())->method('handle');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'OroWorkflowBundle:Widget:widget/transitionComplete.html.twig',
                [
                    'response' => null,
                    'responseCode' => 200,
                    'responseMessage' => 'message1',
                    'transitionSuccess' => true,
                ]
            )
            ->willReturn('content');

        $this->assertEquals(
            new Response('content'),
            $this->helper->createCompleteResponse(new WorkflowItem(), 200, 'message1')
        );
    }

    public function testCreateCompleteResponseWith500Code()
    {
        $this->viewHandler->expects($this->never())->method('handle');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'OroWorkflowBundle:Widget:widget/transitionComplete.html.twig',
                [
                    'response' => null,
                    'responseCode' => 500,
                    'responseMessage' => 'message2',
                    'transitionSuccess' => false,
                ]
            )
            ->willReturn('content');

        $this->assertEquals(
            new Response('content'),
            $this->helper->createCompleteResponse(new WorkflowItem(), 500, 'message2')
        );
    }

    public function testCreateCompleteResponseWithoutCode()
    {
        $view = View::create([
            'workflowItem' => new WorkflowItem(),
        ])->setFormat('json');

        $this->viewHandler->expects($this->once())->method('handle')
            ->with($view)
            ->willReturn(new Response(json_encode('content2'), 200));

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'OroWorkflowBundle:Widget:widget/transitionComplete.html.twig',
                [
                    'response' => 'content2',
                    'responseCode' => 200,
                    'responseMessage' => 'message3',
                    'transitionSuccess' => true,
                ]
            )
            ->willReturn('content');

        $this->assertEquals(
            new Response('content'),
            $this->helper->createCompleteResponse(new WorkflowItem(), null, 'message3')
        );
    }
}
