<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Stub\Extension\Configuration;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class AbstractExtensionTest extends TestCase
{
    private AbstractExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = $this->getMockForAbstractClass(AbstractExtension::class);
        $this->extension->setParameters(new ParameterBag());
    }

    public function testParameters(): void
    {
        $parameters = $this->createMock(ParameterBag::class);

        $this->extension->setParameters($parameters);
        $this->assertEquals($parameters, $this->extension->getParameters());
    }

    public function testGetPriority(): void
    {
        $this->assertSame(0, $this->extension->getPriority(), 'Should be zero by default');
    }

    /**
     * Empty implementation should be callable
     */
    public function testVisitDatasource(): void
    {
        $datasourceMock = $this->createMock(DatasourceInterface::class);
        $config = DatagridConfiguration::create([]);

        $this->extension->visitDatasource($config, $datasourceMock);
    }

    public function testIsApplicable(): void
    {
        $config = DatagridConfiguration::create(
            [ParameterBag::DATAGRID_MODES_PARAMETER => [DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE]]
        );
        self::assertTrue($this->extension->isApplicable($config));
    }

    /**
     * Empty implementation should be callable
     */
    public function testVisitResult(): void
    {
        $result = ResultsObject::create([]);
        $config = DatagridConfiguration::create([]);

        $this->extension->visitResult($config, $result);
    }

    /**
     * Empty implementation should be callable
     */
    public function testVisitMetadata(): void
    {
        $data = MetadataObject::create([]);
        $config = DatagridConfiguration::create([]);

        $this->extension->visitMetadata($config, $data);
    }

    public function testValidateConfiguration(): void
    {
        $configBody = [Configuration::NODE => 'test'];
        $config = [Configuration::ROOT => $configBody];

        $this->assertSame(
            $configBody,
            ReflectionUtil::callMethod($this->extension, 'validateConfiguration', [new Configuration(), $config])
        );
    }
}
