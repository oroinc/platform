<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclVoterPass;

class AclVoterPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder
     */
    protected $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Definition
     */
    protected $aclVoterDefinition;

    /**
     * @var AclVoterPass
     */
    protected $compilerPass;

    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclVoterDefinition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->compilerPass = new AclVoterPass();
    }

    protected function tearDown()
    {
        unset($this->container, $this->aclVoterDefinition, $this->compilerPass);
    }

    public function testProcessNoAclVoter()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(AclVoterPass::ACL_VOTER)
            ->willReturn(false);
        $this->container->expects($this->never())
            ->method('getDefinition');
        $this->aclVoterDefinition->expects($this->never())
            ->method('addMethodCall');

        $this->compilerPass->process($this->container);
    }

    public function testProcessNoConfigProviderProvider()
    {
        $this->container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->willReturnMap(
                [
                    [AclVoterPass::ACL_VOTER, true],
                    [AclVoterPass::SECURITY_CONFIG_PROVIDER, false]
                ]
            );
        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with(AclVoterPass::ACL_VOTER)
            ->willReturn($this->aclVoterDefinition);

        $this->aclVoterDefinition->expects($this->never())
            ->method('addMethodCall');

        $this->compilerPass->process($this->container);
    }

    public function testProcess()
    {
        $this->container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->willReturnMap(
                [
                    [AclVoterPass::ACL_VOTER, true],
                    [AclVoterPass::SECURITY_CONFIG_PROVIDER, true]
                ]
            );
        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with(AclVoterPass::ACL_VOTER)
            ->willReturn($this->aclVoterDefinition);

        $this->aclVoterDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with('setSecurityConfigProvider', [new Reference(AclVoterPass::SECURITY_CONFIG_PROVIDER)]);

        $this->compilerPass->process($this->container);
    }
}
