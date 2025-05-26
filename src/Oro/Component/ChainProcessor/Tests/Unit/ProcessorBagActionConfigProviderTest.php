<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ProcessorBagActionConfigProvider;
use PHPUnit\Framework\TestCase;

class ProcessorBagActionConfigProviderTest extends TestCase
{
    public function testProcessorBagConfigProvider(): void
    {
        $groups = ['group1'];
        $processors = [['processor1', ['group' => 'group1']]];

        $configProvider = new ProcessorBagActionConfigProvider($groups, $processors);

        self::assertSame($groups, $configProvider->getGroups());
        self::assertSame($processors, $configProvider->getProcessors());
    }
}
