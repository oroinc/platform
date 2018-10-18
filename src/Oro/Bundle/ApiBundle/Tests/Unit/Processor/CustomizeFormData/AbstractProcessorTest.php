<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\AbstractProcessor;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;

class AbstractProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider processProvider
     */
    public function testProcess($eventName, $expectedMethodName)
    {
        $context = new CustomizeFormDataContext();
        $context->setEvent($eventName);

        $processor = $this->getMockBuilder(AbstractProcessor::class)
            ->setMethods(['processPreSubmit', 'processSubmit', 'processPostSubmit', 'processFinishSubmit'])
            ->getMockForAbstractClass();

        $processor->expects(self::once())
            ->method($expectedMethodName)
            ->with(self::identicalTo($context));

        $processor->process($context);
    }

    public function processProvider()
    {
        return [
            ['pre_submit', 'processPreSubmit'],
            ['submit', 'processSubmit'],
            ['post_submit', 'processPostSubmit'],
            ['finish_submit', 'processFinishSubmit']
        ];
    }

    public function testShouldNotCallAnyProcessMethodIfEventIsNotKnown()
    {
        $context = new CustomizeFormDataContext();
        $context->setEvent('unknown');

        $processor = $this->getMockBuilder(AbstractProcessor::class)
            ->setMethods(['processPreSubmit', 'processSubmit', 'processPostSubmit', 'processFinishSubmit'])
            ->getMockForAbstractClass();

        $processor->expects(self::never())
            ->method('processPreSubmit');
        $processor->expects(self::never())
            ->method('processSubmit');
        $processor->expects(self::never())
            ->method('processPostSubmit');
        $processor->expects(self::never())
            ->method('processFinishSubmit');

        $processor->process($context);
    }
}
