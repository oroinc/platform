<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\DistributionBundle\DependencyInjection\Compiler\HiddenRoutesPass;
use Oro\Component\Routing\Matcher\PhpMatcherDumper;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;

class HiddenRoutesPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(array $options, array $expectedOptions): void
    {
        $router = new Definition(Router::class);
        $router->addArgument($this->createMock(LoaderInterface::class));
        $router->addArgument('resource');
        $router->addArgument($options);

        $container = new ContainerBuilder();
        $container->setDefinition('router.default', $router);

        (new HiddenRoutesPass())->process($container);

        $routerDefinition = $container->getDefinition('router.default');

        self::assertEquals($expectedOptions, $routerDefinition->getArgument(2));
    }

    public function processDataProvider(): array
    {
        return [
            [[], []],
            [
                [
                    'matcher_dumper_class' => CompiledUrlMatcherDumper::class,
                ],
                [
                    'matcher_dumper_class' => PhpMatcherDumper::class,
                ],
            ],
            [
                [
                    'matcher_dumper_class' => 'OtherMatcherDumper',
                ],
                [
                    'matcher_dumper_class' => 'OtherMatcherDumper',
                ],
            ],
        ];
    }
}
