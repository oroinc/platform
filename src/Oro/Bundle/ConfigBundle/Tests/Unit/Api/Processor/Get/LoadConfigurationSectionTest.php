<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Api\Processor\Get;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationSection;
use Oro\Bundle\ConfigBundle\Api\Processor\Get\LoadConfigurationSection;
use Oro\Bundle\ConfigBundle\Api\Processor\GetScope;
use Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LoadConfigurationSectionTest extends GetProcessorTestCase
{
    /** @var ConfigurationRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $configRepository;

    /** @var LoadConfigurationSection */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configRepository = $this->createMock(ConfigurationRepository::class);

        $this->processor = new LoadConfigurationSection($this->configRepository);
    }

    public function testProcessWhenSectionIsAlreadyLoaded()
    {
        $section = new ConfigurationSection('test');

        $this->configRepository->expects($this->never())
            ->method('getSection');

        $this->context->setResult($section);
        $this->processor->process($this->context);

        $this->assertSame($section, $this->context->getResult());
    }

    public function testProcess()
    {
        $scope = 'scope';
        $section = new ConfigurationSection('test');

        $this->configRepository->expects($this->once())
            ->method('getSection')
            ->with($section->getId(), $scope)
            ->willReturn($section);

        $this->context->setId($section->getId());
        $this->context->set(GetScope::CONTEXT_PARAM, $scope);
        $this->processor->process($this->context);

        $this->assertSame($section, $this->context->getResult());
    }

    public function testProcessForUnknownSection()
    {
        $this->expectException(NotFoundHttpException::class);

        $scope = 'scope';
        $sectionId = 'unknown';

        $this->configRepository->expects($this->once())
            ->method('getSection')
            ->with($sectionId, $scope)
            ->willReturn(null);

        $this->context->setId($sectionId);
        $this->context->set(GetScope::CONTEXT_PARAM, $scope);
        $this->processor->process($this->context);
    }
}
