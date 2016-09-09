<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Api\Processor\GetList;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationSection;
use Oro\Bundle\ConfigBundle\Api\Processor\GetList\LoadConfigurationSections;
use Oro\Bundle\ConfigBundle\Api\Processor\GetScope;

class LoadConfigurationSectionsTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var LoadConfigurationSections */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->configRepository = $this
            ->getMockBuilder('Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();


        $this->processor = new LoadConfigurationSections($this->configRepository, $this->securityFacade);
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

        $this->securityFacade->expects($this->never())
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

        $this->securityFacade->expects($this->once())
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

        $this->securityFacade->expects($this->once())
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
