<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditingConfigurator;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditingExtension;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InlineEditingExtensionTest extends TestCase
{
    private InlineEditingConfigurator&MockObject $configurator;
    private InlineEditingExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->configurator = $this->createMock(InlineEditingConfigurator::class);

        $this->extension = new InlineEditingExtension($this->configurator);
        $this->extension->setParameters(new ParameterBag());
    }

    public function testIsNotApplicableInImportExportMode(): void
    {
        $params = new ParameterBag();
        $params->set(
            ParameterBag::DATAGRID_MODES_PARAMETER,
            [DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE]
        );
        $config = DatagridConfiguration::create([]);
        $this->extension->setParameters($params);
        self::assertFalse($this->extension->isApplicable($config));
    }

    public function testVisitMetadata(): void
    {
        $config = DatagridConfiguration::create([
            'extended_entity_name' => 'Test',
            Configuration::BASE_CONFIG_KEY => ['enable' => true]
        ]);
        $data = MetadataObject::create([]);

        $this->extension->visitMetadata($config, $data);
        $this->assertEquals(
            $config->offsetGet(Configuration::BASE_CONFIG_KEY),
            $data->offsetGet(Configuration::BASE_CONFIG_KEY)
        );
    }

    public function testProcessConfigsWithWrongConfiguration(): void
    {
        $config = DatagridConfiguration::create([Configuration::BASE_CONFIG_KEY => ['enable' => true]]);

        $this->configurator->expects($this->once())
            ->method('configureInlineEditingForGrid')
            ->with($config);
        $this->configurator->expects($this->once())
            ->method('configureInlineEditingForSupportingColumns')
            ->with($config);

        $this->extension->processConfigs($config);
    }
}
