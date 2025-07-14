<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\ReplaceTwigEnvironmentPass;
use Oro\Bundle\UIBundle\Twig\Environment;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Twig\Environment as TwigEnvironment;

class ReplaceTwigEnvironmentPassTest extends TestCase
{
    private ReplaceTwigEnvironmentPass $compiler;
    private ContainerInterface $container;

    #[\Override]
    protected function setUp(): void
    {
        $this->compiler = new ReplaceTwigEnvironmentPass();
        $this->container = new ContainerBuilder();
    }

    public function testProcess(): void
    {
        $options = [];
        $loaderDefinition = new Definition(\stdClass::class);
        $environmentDefinition = new Definition(TwigEnvironment::class, [$loaderDefinition, $options]);
        $this->container->setDefinition('twig.loader', $loaderDefinition);
        $this->container->setDefinition('twig', $environmentDefinition);
        $this->compiler->process($this->container);

        $twigDefinition = $this->container->getDefinition('twig');

        self::assertEquals(Environment::class, $twigDefinition->getClass());
        self::assertTrue($twigDefinition->getArgument('$options')['use_yield']);
    }
}
