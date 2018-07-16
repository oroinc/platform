<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Api\Processor\GetList;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationSection;
use Oro\Bundle\ConfigBundle\Api\Processor\GetList\LoadConfigurationSections;
use Oro\Bundle\ConfigBundle\Api\Processor\GetScope;
use Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class LoadConfigurationSectionsTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var LoadConfigurationSections */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->configRepository = $this->createMock(ConfigurationRepository::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->processor = new LoadConfigurationSections($this->configRepository, $this->authorizationChecker);
    }

    public function testProcessWhenSectionsAreAlreadyLoaded()
    {
        $section = new ConfigurationSection('test');

        $this->configRepository->expects($this->never())
            ->method('getSection');

        $this->context->setResult([$section]);
        $this->processor->process($this->context);

        $this->assertEquals([$section], $this->context->getResult());
    }

    public function testProcessWhenAclResourceIsNotSet()
    {
        $scope = 'scope';
        $section = new ConfigurationSection('test');
        $config = new EntityDefinitionConfig();

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->configRepository->expects($this->once())
            ->method('getSectionIds')
            ->willReturn([$section->getId()]);
        $this->configRepository->expects($this->once())
            ->method('getSection')
            ->with($section->getId(), $scope)
            ->willReturn($section);

        $this->context->set(GetScope::CONTEXT_PARAM, $scope);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $this->assertEquals([$section], $this->context->getResult());
    }

    public function testProcessWhenAclResourceExistsAndAccessIsGranted()
    {
        $scope = 'scope';
        $section = new ConfigurationSection('test');
        $config = new EntityDefinitionConfig();
        $config->setAclResource('test_acl_resource');

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($config->getAclResource())
            ->willReturn(true);

        $this->configRepository->expects($this->once())
            ->method('getSectionIds')
            ->willReturn([$section->getId()]);
        $this->configRepository->expects($this->once())
            ->method('getSection')
            ->with($section->getId(), $scope)
            ->willReturn($section);

        $this->context->set(GetScope::CONTEXT_PARAM, $scope);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $this->assertEquals([$section], $this->context->getResult());
    }

    public function testProcessWhenAclResourceExistsAndAccessIsDenied()
    {
        $scope = 'scope';
        $config = new EntityDefinitionConfig();
        $config->setAclResource('test_acl_resource');

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($config->getAclResource())
            ->willReturn(false);

        $this->configRepository->expects($this->never())
            ->method('getSectionIds');
        $this->configRepository->expects($this->never())
            ->method('getSection');

        $this->context->set(GetScope::CONTEXT_PARAM, $scope);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $this->assertEquals([], $this->context->getResult());
    }
}
