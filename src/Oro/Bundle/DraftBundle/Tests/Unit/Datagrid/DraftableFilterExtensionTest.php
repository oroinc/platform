<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DraftBundle\Acl\AccessRule\DraftAccessRule;
use Oro\Bundle\DraftBundle\Datagrid\DraftableFilterExtension;
use Oro\Bundle\DraftBundle\Manager\DraftableFilterManager;

class DraftableFilterExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var DraftableFilterManager|\PHPUnit\Framework\MockObject\MockObject */
    private $filterManager;

    /** @var DraftAccessRule|\PHPUnit\Framework\MockObject\MockObject */
    private $draftAccessRule;

    /** @var DraftableFilterExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->filterManager = $this->createMock(DraftableFilterManager::class);
        $this->draftAccessRule = $this->createMock(DraftAccessRule::class);

        $this->extension = new DraftableFilterExtension(
            $this->filterManager,
            $this->draftAccessRule
        );
    }

    /**
     * @dataProvider getIsApplicableDataProvider
     */
    public function testIsApplicable(array $parameters, bool $expectedResult): void
    {
        $datagridConfig = DatagridConfiguration::createNamed('test_grid', $parameters);

        $this->extension->setParameters(new ParameterBag());

        $this->assertEquals($expectedResult, $this->extension->isApplicable($datagridConfig));
    }

    public function getIsApplicableDataProvider(): array
    {
        return [
            'the option is not set' => [
                'parameters' => [
                    'options' => []
                ],
                'expectedResult' => false,
            ],
            'the option as false' => [
                'parameters' => [
                    'options' => [
                        'showDrafts' => false
                    ]
                ],
                'expectedResult' => false,
            ],
            'the option as true' => [
                'parameters' => [
                    'options' => [
                        'showDrafts' => true
                    ]
                ],
                'expectedResult' => true,
            ],
        ];
    }

    public function testVisitDatasource(): void
    {
        $className = 'className';
        $config = $this->getDatagridConfiguration($className);
        $datasource = $this->createMock(DatasourceInterface::class);

        $this->filterManager->expects($this->once())
            ->method('disable')
            ->with($className);

        $this->draftAccessRule->expects($this->once())
            ->method('setEnabled')
            ->with(true);

        $this->extension->visitDatasource($config, $datasource);
    }

    public function testVisitResult(): void
    {
        $className = 'className';
        $config = $this->getDatagridConfiguration($className);
        $datasource = $this->createMock(DatasourceInterface::class);

        $this->filterManager->expects($this->once())
            ->method('disable')
            ->with($className);
        $this->filterManager->expects($this->once())
            ->method('enable')
            ->with($className);

        $this->draftAccessRule->expects($this->exactly(2))
            ->method('setEnabled')
            ->withConsecutive(
                [true],
                [false]
            );

        $this->extension->visitDatasource($config, $datasource);

        $resultsObject = $this->createMock(ResultsObject::class);
        $this->extension->visitResult($config, $resultsObject);
    }

    private function getDatagridConfiguration(string $className): DatagridConfiguration
    {
        $fromPart = [['table' => $className]];

        $queryConfiguration = $this->createMock(OrmQueryConfiguration::class);
        $queryConfiguration->expects($this->once())
            ->method('getFrom')
            ->willReturn($fromPart);

        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects($this->once())
            ->method('getOrmQuery')
            ->willReturn($queryConfiguration);

        return $config;
    }
}
