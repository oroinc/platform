<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Provider;

use Oro\Bundle\FormBundle\Provider\CallbackFormTemplateDataProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Stub\CallableStub;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class CallbackFormTemplateDataProviderTest extends \PHPUnit\Framework\TestCase
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

        $callable->expects($this->once())->method('__invoke')->with($data, $form, $request)->willReturn(['payload']);

        $handler = new CallbackFormTemplateDataProvider($callable);

        $this->assertEquals(['payload'], $handler->getData($data, $form, $request));
    }
}
