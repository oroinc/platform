<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestParameterBagFactoryTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_NAME = 'testGrid';
    private const PARAMETERS_CLASS = ParameterBag::class;

    private RequestStack $requestStack;
    private RequestParameterBagFactory $factory;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->factory = new RequestParameterBagFactory(self::PARAMETERS_CLASS, $this->requestStack);
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testCreateParameters(array $query, array $expectedParameters): void
    {
        $request = new Request($query);
        $this->requestStack->push($request);
        $parameters = $this->factory->createParameters(self::TEST_NAME);

        $this->assertEquals($expectedParameters, $parameters->all());
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testCreateParametersFromRequest(array $query, array $expectedParameters): void
    {
        $parameters = $this->factory->createParametersFromRequest(new Request($query), self::TEST_NAME);

        $this->assertEquals($expectedParameters, $parameters->all());
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testFetchParameters(array $query, array $expectedParameters): void
    {
        $request = new Request($query);
        $this->requestStack->push($request);
        $parameters = $this->factory->fetchParameters(self::TEST_NAME);

        $this->assertEquals($expectedParameters, $parameters);
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testFetchParametersFromRequest(array $query, array $expectedParameters): void
    {
        $parameters = $this->factory->fetchParametersFromRequest(new Request($query), self::TEST_NAME);

        $this->assertEquals($expectedParameters, $parameters);
    }

    public function queryDataProvider(): array
    {
        return [
            'empty query' => [
                '$query' => [],
                '$expectedParameters' => [],
            ],
            'regular parameters' => [
                '$query' => [self::TEST_NAME => ['sample_key' => 'sample_value']],
                '$expectedParameters' => ['sample_key' => 'sample_value'],
            ],
            'minified parameters' => [
                '$query' => ['grid' => [self::TEST_NAME => 'sample_key=sample_value']],
                '$expectedParameters' => [ParameterBag::MINIFIED_PARAMETERS => ['sample_key' => 'sample_value']],
            ],
            'both regular and minified parameters' => [
                '$query' => [
                    self::TEST_NAME => ['sample_key' => 'sample_value'],
                    'grid' => [self::TEST_NAME => 'sample_key=sample_value'],
                ],
                '$expectedParameters' => [
                    'sample_key' => 'sample_value',
                    ParameterBag::MINIFIED_PARAMETERS => ['sample_key' => 'sample_value'],
                ],
            ],
            'invalid minified parameters' => [
                '$query' => ['grid' => [self::TEST_NAME => []]],
                '$expectedParameters' => [],
            ],
        ];
    }
}
