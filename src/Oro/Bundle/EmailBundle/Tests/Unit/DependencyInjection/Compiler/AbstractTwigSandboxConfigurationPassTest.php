<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection\Compiler\Stub\TwigSandboxConfigurationPassStub;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

class AbstractTwigSandboxConfigurationPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var CompilerPassInterface */
    private $compilerPass;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder */
    private $containerBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);
        $this->compilerPass = new TwigSandboxConfigurationPassStub();
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @expectedExceptionMessage You have requested a non-existent service "oro_email.twig.email_security_policy"
     */
    public function testProcessWithoutEmailSecurityPoliceService()
    {
        $exception = new ServiceNotFoundException(
            TwigSandboxConfigurationPassStub::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY
        );
        $this->containerBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with(TwigSandboxConfigurationPassStub::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY)
            ->willThrowException($exception);

        $this->compilerPass->process($this->containerBuilder);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @expectedExceptionMessage You have requested a non-existent service "oro_email.email_renderer"
     */
    public function testProcessWithoutEmailRendererService()
    {
        $exception = new ServiceNotFoundException(
            TwigSandboxConfigurationPassStub::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY
        );

        $securityPolicyDef = $this->createMock(Definition::class);
        $securityPolicyDef->expects($this->exactly(2))
            ->method('replaceArgument');
        $securityPolicyDef->expects($this->exactly(2))
            ->method('getArgument')
            ->withConsecutive(
                [4],
                [1]
            )
            ->willReturn([]);

        $this->containerBuilder
            ->expects($this->exactly(3))
            ->method('getDefinition')
            ->withConsecutive(
                [TwigSandboxConfigurationPassStub::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY],
                [TwigSandboxConfigurationPassStub::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY],
                [TwigSandboxConfigurationPassStub::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY]
            )
            ->willReturnCallback(function ($arg) use ($securityPolicyDef, $exception) {
                if ($arg === TwigSandboxConfigurationPassStub::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY) {
                    return $securityPolicyDef;
                }
                if ($arg === TwigSandboxConfigurationPassStub::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY) {
                    throw $exception;
                }
            });

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcess()
    {
        $securityPolicyDef = $this->createMock(Definition::class);
        $securityPolicyDef->expects($this->exactly(2))
            ->method('replaceArgument');
        $securityPolicyDef->expects($this->exactly(2))
            ->method('getArgument')
            ->withConsecutive(
                [4],
                [1]
            )
            ->willReturn([]);

        $rendererDef = $this->createMock(Definition::class);
        $rendererDef->expects($this->exactly(2))
            ->method('addMethodCall')
            ->withConsecutive(
                [
                    'addExtension',
                    [new Reference('extension1')]
                ],
                [
                    'addExtension',
                    [new Reference('extension2')]
                ]
            );

        $this->containerBuilder
            ->expects($this->exactly(3))
            ->method('getDefinition')
            ->withConsecutive(
                [TwigSandboxConfigurationPassStub::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY],
                [TwigSandboxConfigurationPassStub::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY],
                [TwigSandboxConfigurationPassStub::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY]
            )
            ->willReturnMap([
                [
                    TwigSandboxConfigurationPassStub::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY,
                    $securityPolicyDef
                ],
                [
                    TwigSandboxConfigurationPassStub::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY,
                    $rendererDef
                ]
            ]);

        $this->compilerPass->process($this->containerBuilder);
    }
}
