<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeErrors;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class NormalizeErrorsTest extends GetProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var NormalizeErrors */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->processor = new NormalizeErrors($this->translator);
    }

    public function testProcessWithoutErrors()
    {
        $this->translator->expects($this->never())
            ->method('trans');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $error = Error::create(new Label('error title'));

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('error title')
            ->willReturn('translated error title');

        $this->context->addError($error);
        $this->processor->process($this->context);

        $expectedError = Error::create('translated error title');

        $this->assertEquals([$expectedError], $this->context->getErrors());
    }
}
