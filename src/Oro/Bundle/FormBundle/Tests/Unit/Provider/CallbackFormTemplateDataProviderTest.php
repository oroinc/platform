<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Provider;

use Oro\Bundle\FormBundle\Provider\CallbackFormTemplateDataProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Stub\CallableStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class CallbackFormTemplateDataProviderTest extends TestCase
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
            ->willReturn(['payload']);

        $handler = new CallbackFormTemplateDataProvider($callable);

        $this->assertEquals(['payload'], $handler->getData($data, $form, $request));
    }
}
