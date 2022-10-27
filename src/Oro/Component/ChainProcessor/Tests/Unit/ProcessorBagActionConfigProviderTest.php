<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ProcessorBagActionConfigProvider;

class ProcessorBagActionConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessorBagConfigProvider()
    {
        $groups = ['group1'];
        $processors = [['processor1', ['group' => 'group1']]];

        $configProvider = new ProcessorBagActionConfigProvider($groups, $processors);

        self::assertSame($groups, $configProvider->getGroups());
        self::assertSame($processors, $configProvider->getProcessors());
    }
}
