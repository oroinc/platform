<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Model\Update;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class UpdateTest extends TestCase
{
    public function testUpdateMethods(): void
    {
        $data = new \stdClass();
        $form = $this->createMock(FormInterface::class);
        $formHandler = $this->createMock(FormHandlerInterface::class);
        $formTemplateDataProvider = $this->createMock(FormTemplateDataProviderInterface::class);
        $update = new Update();

        $update->setFormData($data);
        $update->setFrom($form);
        $update->setHandler($formHandler);
        $update->setTemplateDataProvider($formTemplateDataProvider);

        $this->assertSame($data, $update->getFormData());
        $this->assertSame($form, $update->getForm());

        $request = $this->createMock(Request::class);

        $formHandler->expects($this->once())
            ->method('process')->with($data, $form, $request)->willReturn(true);
        $this->assertTrue($update->handle($request), 'Handling result bubbled without changes.');

        $formTemplateDataProvider->expects($this->once())
            ->method('getData')->with($data, $form, $request)->willReturn(['data' => 'value']);
        $this->assertEquals(
            ['data' => 'value'],
            $update->getTemplateData($request),
            'Provider result bubbled without changes.'
        );
    }
}
