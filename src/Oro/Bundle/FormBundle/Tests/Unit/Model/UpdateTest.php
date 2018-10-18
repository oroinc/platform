<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Model\Update;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class UpdateTest extends \PHPUnit\Framework\TestCase
{
    public function testUpdateMethods()
    {
        $data = new \stdClass();
        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);
        /** @var FormHandlerInterface|MockObject $formHandler */
        $formHandler = $this->createMock(FormHandlerInterface::class);
        /** @var FormTemplateDataProviderInterface|MockObject $formTemplateDataProvider */
        $formTemplateDataProvider = $this->createMock(FormTemplateDataProviderInterface::class);
        $update = new  Update();

        $update->setFormData($data);
        $update->setFrom($form);
        $update->setHandler($formHandler);
        $update->setTemplateDataProvider($formTemplateDataProvider);

        $this->assertSame($data, $update->getFormData());
        $this->assertSame($form, $update->getForm());

        /** @var Request|MockObject $request */
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
