<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection\Compiler\Stub\TwigSandboxConfigurationPassStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

class AbstractTwigSandboxConfigurationPassTest extends TestCase
{
    private CompilerPassInterface $compiler;

    #[\Override]
    protected function setUp(): void
    {
        $this->compiler = new TwigSandboxConfigurationPassStub();
    }

    public function testProcessWithoutEmailSecurityPoliceService(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(
            'You have requested a non-existent service "oro_email.twig.email_security_policy"'
        );

        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcessWithoutEmailRendererService(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('You have requested a non-existent service "oro_email.twig.email_environment"');

        $container = new ContainerBuilder();
        $container->register('oro_email.twig.email_security_policy')
            ->setArguments([[], [], [], [], []]);

        $this->compiler->process($container);
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $securityPolicyDef = $container->register('oro_email.twig.email_security_policy')
            ->addMethodCall('setAllowedTags', [['some_existing_tag']])
            ->addMethodCall('setAllowedFunctions', [['some_existing_function']])
            ->addMethodCall('setAllowedFilters', [['some_existing_filter']]);
        $rendererDef = $container->register('oro_email.twig.email_environment');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['setAllowedTags', [['some_existing_tag', 'tag1', 'tag2']]],
                ['setAllowedFunctions', [['some_existing_function', 'function1', 'function2']]],
                ['setAllowedFilters', [['some_existing_filter', 'filter1', 'filter2']]],
            ],
            $securityPolicyDef->getMethodCalls()
        );
        self::assertEquals(
            [
                ['addExtension', [new Reference('extension1')]],
                ['addExtension', [new Reference('extension2')]]
            ],
            $rendererDef->getMethodCalls()
        );
    }
}
