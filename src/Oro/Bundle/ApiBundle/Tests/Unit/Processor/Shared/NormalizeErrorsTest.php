<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeErrors;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Symfony\Component\Translation\TranslatorInterface;

class NormalizeErrorsTest extends GetProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var NormalizeErrors */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->processor = new NormalizeErrors($this->translator);
    }

    public function testProcessWithoutErrors()
    {
        $this->translator->expects(self::never())
            ->method('trans');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $error = Error::create(new Label('error title'));

        $this->translator->expects(self::once())
            ->method('trans')
            ->with('error title')
            ->willReturn('translated error title');

        $this->context->addError($error);
        $this->processor->process($this->context);

        $expectedError = Error::create('translated error title');

        self::assertEquals([$expectedError], $this->context->getErrors());
    }
}
