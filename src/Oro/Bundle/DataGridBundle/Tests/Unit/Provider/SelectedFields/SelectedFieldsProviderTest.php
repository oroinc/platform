<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider\SelectedFields;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProvider;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface;

class SelectedFieldsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var SelectedFieldsProvider */
    private $compositeProvider;

    /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridConfiguration;

    /** @var ParameterBag|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridParameters;

    protected function setUp()
    {
        $this->compositeProvider = new SelectedFieldsProvider();

        $this->datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $this->datagridParameters = $this->createMock(ParameterBag::class);
    }

    public function testGetSelectedFieldsWhenNoProviders(): void
    {
        self::assertEmpty(
            $this->compositeProvider->getSelectedFields($this->datagridConfiguration, $this->datagridParameters)
        );
    }

    public function testGetSelectedFieldsWhenSingleProvider(): void
    {
        $expectedFields = ['sampleField1', 'sampleField2'];
        $selectedFieldsProvider = $this->createSelectedFieldsProvider($expectedFields);

        $this->compositeProvider->addSelectedFieldsProvider($selectedFieldsProvider);
        $selectedFields = $this->compositeProvider
            ->getSelectedFields($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedFields, $selectedFields);
    }

    public function testGetSelectedFieldsWhenMultipleProvidersIntersecting(): void
    {
        $expectedFields = ['sampleField1', 'sampleField2', 'sampleField3'];
        $selectedFieldsProvider1 = $this->createSelectedFieldsProvider(['sampleField1', 'sampleField2']);
        $selectedFieldsProvider2 = $this->createSelectedFieldsProvider(['sampleField3', 'sampleField2']);

        $this->compositeProvider->addSelectedFieldsProvider($selectedFieldsProvider1);
        $this->compositeProvider->addSelectedFieldsProvider($selectedFieldsProvider2);

        $selectedFields = $this->compositeProvider
            ->getSelectedFields($this->datagridConfiguration, $this->datagridParameters);

        self::assertEquals($expectedFields, $selectedFields);
    }

    /**
     * @param array $fields
     *
     * @return SelectedFieldsProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createSelectedFieldsProvider(array $fields): SelectedFieldsProviderInterface
    {
        $selectedFieldsProvider = $this->createMock(SelectedFieldsProvider::class);
        $selectedFieldsProvider
            ->expects(self::once())
            ->method('getSelectedFields')
            ->with($this->datagridConfiguration, $this->datagridParameters)
            ->willReturn($fields);

        return $selectedFieldsProvider;
    }
}
