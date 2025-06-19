<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\FormBundle\Form\Handler\CallbackFormHandler;
use Oro\Bundle\TestFrameworkBundle\Test\Stub\CallableStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class CallbackFormHandlerTest extends TestCase
{
    public function testInterface(): void
    {
        $data = (object)[];
        $form = $this->createMock(FormInterface::class);
        $request = $this->createMock(Request::class);

        $callable = $this->createMock(CallableStub::class);
        $callable->expects($this->once())
            ->method('__invoke')
            ->with($data, $form, $request)
            ->willReturn(true);

        $handler = new CallbackFormHandler($callable);

        $this->assertTrue($handler->process($data, $form, $request));
    }
}
