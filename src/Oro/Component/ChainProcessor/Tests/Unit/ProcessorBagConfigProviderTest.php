<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ProcessorBagConfigProvider;

class ProcessorBagConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessorBagConfigProvider()
    {
        $groups = ['action1' => ['group1']];
        $processors = [
            'action1' => [['processor1', ['group' => 'group1']]],
            'action2' => [['processor1', []]]
        ];

        $processorBagConfigProvider = new ProcessorBagConfigProvider($groups, $processors);

        self::assertSame(['action1', 'action2'], $processorBagConfigProvider->getActions());
        self::assertSame($groups['action1'], $processorBagConfigProvider->getGroups('action1'));
        self::assertSame([], $processorBagConfigProvider->getGroups('action2'));
        self::assertSame($processors['action1'], $processorBagConfigProvider->getProcessors('action1'));
        self::assertSame($processors['action2'], $processorBagConfigProvider->getProcessors('action2'));
        self::assertSame([], $processorBagConfigProvider->getProcessors('action3'));
    }
}
