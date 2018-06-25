<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EmailBundle\Form\Handler\EmailHandler;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailHandlerTest extends \PHPUnit\Framework\TestCase
{
    const FORM_DATA = ['field' => 'value'];

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $emailProcessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var EmailHandler */
    protected $handler;

    /** @var Email */
    protected $model;

    protected function setUp()
    {
        $this->form                = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $this->request             = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);

        $this->emailProcessor      = $this->getMockBuilder('Oro\Bundle\EmailBundle\Mailer\Processor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger              = $this->createMock('Psr\Log\LoggerInterface');

        $this->model = new Email();

        $this->handler = new EmailHandler(
            $this->form,
            $requestStack,
            $this->emailProcessor,
            $this->logger
        );
    }

    public function testProcessGetRequestWithEmptyQueryString()
    {
        $this->request->setMethod('GET');

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->model);

        $this->form->expects($this->never())
            ->method('submit');

        $this->assertFalse($this->handler->process($this->model));
    }

    /**
     * @param string $method
     * @param bool $valid
     * @param bool $assert
     *
     * @dataProvider processData
     */
    public function testProcessData($method, $valid, $assert)
    {
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);
        $this->model
            ->setFrom('from@example.com')
            ->setTo(['to@example.com'])
            ->setSubject('testSubject')
            ->setBody('testBody');

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->model);

        if (in_array($method, ['POST', 'PUT'])) {
            $this->form->expects($this->once())
                ->method('submit')
                ->with(self::FORM_DATA);

            $this->form->expects($this->once())
                ->method('isValid')
                ->will($this->returnValue($valid));

            if ($valid) {
                $this->emailProcessor->expects($this->once())
                    ->method('process')
                    ->with($this->model);
            }
        }

        $this->assertEquals($assert, $this->handler->process($this->model));
    }

    /**
     * @param string $method
     *
     * @dataProvider methodsData
     */
    public function testProcessException($method)
    {
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);
        $this->model
            ->setFrom('from@example.com')
            ->setTo(['to@example.com'])
            ->setSubject('testSubject')
            ->setBody('testBody');

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->model);

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $exception = new \Exception('TEST');
        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with($this->model)
            ->will(
                $this->returnCallback(
                    function () use ($exception) {
                        throw $exception;
                    }
                )
            );

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Email sending failed.', ['exception' => $exception]);
        $this->form->expects($this->once())
            ->method('addError')
            ->with($this->isInstanceOf('Symfony\Component\Form\FormError'));
        $this->assertFalse($this->handler->process($this->model));
    }

    public function processData()
    {
        return [
            ['POST', true, true],
            ['POST', false, false],
            ['PUT', true, true],
            ['PUT', false, false],
            ['GET', true, false],
            ['GET', false, false],
            ['DELETE', true, false],
            ['DELETE', false, false]
        ];
    }

    public function methodsData()
    {
        return [
            ['POST'],
            ['PUT']
        ];
    }
}
