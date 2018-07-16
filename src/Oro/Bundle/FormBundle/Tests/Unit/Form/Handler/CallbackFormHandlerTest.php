<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\FormBundle\Form\Handler\CallbackFormHandler;
use Oro\Bundle\TestFrameworkBundle\Test\Stub\CallableStub;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class CallbackFormHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testInterface()
    {
        $data = (object)[];
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(Request::class);

        /** @var CallableStub|\PHPUnit\Framework\MockObject\MockObject $callable */
        $callable = $this->createMock(CallableStub::class);
        $callable->expects($this->once())->method('__invoke')->with($data, $form, $request)->willReturn(true);

        $handler = new CallbackFormHandler($callable);

        $this->assertTrue($handler->process($data, $form, $request));
    }
}
