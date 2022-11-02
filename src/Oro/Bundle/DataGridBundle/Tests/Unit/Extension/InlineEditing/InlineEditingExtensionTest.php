<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditingConfigurator;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditingExtension;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;

class InlineEditingExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var InlineEditingConfigurator|\PHPUnit\Framework\MockObject\MockObject */
    private $configurator;

    /** @var InlineEditingExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->configurator = $this->createMock(InlineEditingConfigurator::class);

        $this->extension = new InlineEditingExtension($this->configurator);
        $this->extension->setParameters(new ParameterBag());
    }

    public function testIsNotApplicableInImportExportMode()
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

    public function testVisitMetadata()
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

    public function testProcessConfigsWithWrongConfiguration()
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
