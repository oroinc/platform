<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\LazyProcessorBagConfigProvider;
use Oro\Component\ChainProcessor\ProcessorBagActionConfigProvider;
use Psr\Container\ContainerInterface;

class LazyProcessorBagConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessorBagConfigProvider()
    {
        $groups = ['action1' => ['group1']];
        $processors = [
            'action1' => [['processor1', ['group' => 'group1']]],
            'action2' => [['processor1', []]]
        ];

        $provider1 = new ProcessorBagActionConfigProvider($groups['action1'], $processors['action1']);
        $provider2 = new ProcessorBagActionConfigProvider([], $processors['action2']);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::any())
            ->method('has')
            ->willReturnCallback(function ($id) use ($processors) {
                return isset($processors[$id]);
            });
        $container->expects(self::any())
            ->method('get')
            ->willReturnCallback(function ($id) use ($provider1, $provider2) {
                if ('action1' === $id) {
                    return $provider1;
                }
                if ('action2' === $id) {
                    return $provider2;
                }

                throw new \LogicException(sprintf('The service "%s" does not exist.', $id));
            });

        $processorBagConfigProvider = new LazyProcessorBagConfigProvider(
            ['action1', 'action2'],
            $container
        );

        self::assertSame(['action1', 'action2'], $processorBagConfigProvider->getActions());
        self::assertSame($groups['action1'], $processorBagConfigProvider->getGroups('action1'));
        self::assertSame([], $processorBagConfigProvider->getGroups('action2'));
        self::assertSame($processors['action1'], $processorBagConfigProvider->getProcessors('action1'));
        self::assertSame($processors['action2'], $processorBagConfigProvider->getProcessors('action2'));
        self::assertSame([], $processorBagConfigProvider->getProcessors('action3'));
    }
}
