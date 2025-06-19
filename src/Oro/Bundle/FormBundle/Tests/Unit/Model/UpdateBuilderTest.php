<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Model\UpdateBuilder;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class UpdateBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $data = (object)[];
        $form = $this->createMock(FormInterface::class);
        $handler = $this->createMock(FormHandlerInterface::class);
        $provider = $this->createMock(FormTemplateDataProviderInterface::class);

        $update = (new UpdateBuilder())->build($data, $form, $handler, $provider);

        $this->assertSame($data, $update->getFormData());
        $this->assertSame($form, $update->getForm());
        $request = $this->createMock(Request::class);
        //implicit injections call assertions
        $handler->expects($this->once())
            ->method('process')
            ->with($data, $form, $request);
        $update->handle($request);
        $provider->expects($this->once())
            ->method('getData')
            ->with($data, $form, $request);
        $update->getTemplateData($request);
    }
}
