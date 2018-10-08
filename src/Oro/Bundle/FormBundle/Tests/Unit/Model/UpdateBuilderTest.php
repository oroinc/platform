<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Model\Update;
use Oro\Bundle\FormBundle\Model\UpdateBuilder;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class UpdateBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        $data = (object)[];
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        /** @var FormHandlerInterface|\PHPUnit\Framework\MockObject\MockObject $handler */
        $handler = $this->createMock(FormHandlerInterface::class);
        /** @var FormTemplateDataProviderInterface|\PHPUnit\Framework\MockObject\MockObject $provider */
        $provider = $this->createMock(FormTemplateDataProviderInterface::class);

        $update = (new UpdateBuilder())->build($data, $form, $handler, $provider);

        $this->assertSame($data, $update->getFormData());
        $this->assertSame($form, $update->getForm());
        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(Request::class);
        //implicit injections call assertions
        $handler->expects($this->once())->method('process')->with($data, $form, $request);
        $update->handle($request);
        $provider->expects($this->once())->method('getData')->with($data, $form, $request);
        $update->getTemplateData($request);
    }
}
