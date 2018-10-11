<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Tools;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Tools\DatagridParametersHelper;

class DatagridParametersHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ParameterBag|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridParameters;

    /** @var DatagridParametersHelper */
    private $datagridParametersHelper;

    protected function setUp()
    {
        $this->datagridParameters = $this->createMock(ParameterBag::class);

        $this->datagridParametersHelper = new DatagridParametersHelper();
    }

    public function testGetFromParametersWhenParameterNotExists(): void
    {
        $this->mockParameterExists($parameterName = 'sampleParameterName', false);

        $this->datagridParameters
            ->expects(self::never())
            ->method('get');

        self::assertNull($this->datagridParametersHelper->getFromParameters($this->datagridParameters, $parameterName));
    }

    /**
     * @param string $parameterName
     * @param bool $exists
     */
    private function mockParameterExists(string $parameterName, bool $exists): void
    {
        $this->datagridParameters
            ->expects(self::once())
            ->method('has')
            ->with($parameterName)
            ->willReturn($exists);
    }

    public function testGetFromParametersWhenParameterExists(): void
    {
        $this->mockParameterExists($parameterName = 'sampleParameterName', true);

        $this->datagridParameters
            ->expects(self::once())
            ->method('get')
            ->with($parameterName)
            ->willReturn($parameterValue = 'parameterValue');

        self::assertEquals(
            $parameterValue,
            $this->datagridParametersHelper->getFromParameters($this->datagridParameters, $parameterName)
        );
    }

    public function testGetFromMinifiedParametersWhenMinifiedParametersNotExists(): void
    {
        $this->mockParameterExists($parameterName = ParameterBag::MINIFIED_PARAMETERS, false);

        $this->datagridParameters
            ->expects(self::never())
            ->method('get');

        self::assertNull($this->datagridParametersHelper->getFromParameters($this->datagridParameters, $parameterName));
    }

    /**
     * @dataProvider minifiedParametersDataProvider
     *
     * @param array $minifiedParameters
     * @param string $parameterName
     * @param string|null $expectedParameterValue
     */
    public function testGetFromMinifiedParameters(
        array $minifiedParameters,
        string $parameterName,
        ?string $expectedParameterValue
    ): void {
        $this->mockParameterExists(ParameterBag::MINIFIED_PARAMETERS, true);

        $this->datagridParameters
            ->expects(self::once())
            ->method('get')
            ->with(ParameterBag::MINIFIED_PARAMETERS)
            ->willReturn($minifiedParameters);

        self::assertSame(
            $expectedParameterValue,
            $this->datagridParametersHelper->getFromMinifiedParameters($this->datagridParameters, $parameterName)
        );
    }

    /**
     * @return array
     */
    public function minifiedParametersDataProvider(): array
    {
        return [
            'minified parameter does not exist' => [
                'minifiedParameters' => [],
                'parameterName' => 'sampleParameterName',
                'expectedParameterValue' => null,
            ],
            'minified parameter exists' => [
                'minifiedParameters' => ['sampleParameterName' => 'sampleParameterValue'],
                'parameterName' => 'sampleParameterName',
                'expectedParameterValue' => 'sampleParameterValue',
            ],
        ];
    }
}
