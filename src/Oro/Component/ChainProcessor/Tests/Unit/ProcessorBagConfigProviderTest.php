<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ProcessorBagConfigProvider;

class ProcessorBagConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessorBagConfigProvider()
    {
        $groups = ['action1' => ['group1']];
        $processors = ['action1' => [['processor1', ['group' => 'group1']]]];

        $processorBagConfigProvider = new ProcessorBagConfigProvider($groups, $processors);

        self::assertEquals($groups, $processorBagConfigProvider->getGroups());
        self::assertEquals($processors, $processorBagConfigProvider->getProcessors());
    }
}
