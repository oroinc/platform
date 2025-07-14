<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox;

use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityFormatExtension;
use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityFormatExtensionFactory;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityFormatExtensionFactoryTest extends TestCase
{
    private TemplateRendererConfigProviderInterface&MockObject $templateRendererConfigProvider;
    private EntityFormatExtensionFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->templateRendererConfigProvider = $this->createMock(TemplateRendererConfigProviderInterface::class);
        $this->factory = new EntityFormatExtensionFactory($this->templateRendererConfigProvider);
    }

    public function testCreateWhenEmptyConfig(): void
    {
        $configuration = [];
        $this->templateRendererConfigProvider->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $expected = new EntityFormatExtension();
        $expected->setFormatters([]);

        self::assertEquals($expected, ($this->factory)());
    }

    public function testCreate(): void
    {
        $configuration = [
            TemplateRendererConfigProviderInterface::DEFAULT_FORMATTERS => [
                'Acme\Entity\SampleEntity' => ['sampleFieldName' => 'sample_formatter_name'],
            ],
        ];
        $this->templateRendererConfigProvider->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $expected = new EntityFormatExtension();
        $expected->setFormatters($configuration[TemplateRendererConfigProviderInterface::DEFAULT_FORMATTERS]);

        self::assertEquals($expected, ($this->factory)());
    }
}
