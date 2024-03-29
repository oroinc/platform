<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox;

use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityDataAccessor;
use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariableComputer;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateData;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateDataFactory;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TemplateDataFactoryTest extends TestCase
{
    private TemplateRendererConfigProviderInterface|MockObject $templateRendererConfigProvider;

    private EntityVariableComputer|MockObject $entityVariableComputer;

    private EntityDataAccessor|MockObject $entityDataAccessor;

    private TemplateDataFactory $factory;

    protected function setUp(): void
    {
        $this->templateRendererConfigProvider = $this->createMock(TemplateRendererConfigProviderInterface::class);
        $this->entityVariableComputer = $this->createMock(EntityVariableComputer::class);
        $this->entityDataAccessor = $this->createMock(EntityDataAccessor::class);

        $this->factory = new TemplateDataFactory(
            $this->templateRendererConfigProvider,
            $this->entityVariableComputer,
            $this->entityDataAccessor
        );
    }

    public function testCreateTemplateData(): void
    {
        $systemVars = ['system_key' => 'system_value'];
        $templateParams = ['sample_key' => 'sample_value', 'system' => $systemVars];
        $this->templateRendererConfigProvider
            ->expects(self::once())
            ->method('getSystemVariableValues')
            ->willReturn($systemVars);

        self::assertEquals(
            new TemplateData(
                $templateParams,
                $this->entityVariableComputer,
                $this->entityDataAccessor,
                'system',
                'entity',
                'computed'
            ),
            $this->factory->createTemplateData($templateParams)
        );
    }
}
