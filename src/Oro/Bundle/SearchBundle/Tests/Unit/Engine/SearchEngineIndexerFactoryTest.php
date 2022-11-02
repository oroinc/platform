<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Engine\EngineParameters;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Engine\SearchEngineIndexerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SearchEngineIndexerFactoryTest extends TestCase
{
    private EngineParameters $engineParametersBagMock;

    private ServiceLocator $locatorMock;

    protected function setUp(): void
    {
        $this->engineParametersBagMock = self::createMock(EngineParameters::class);
        $this->engineParametersBagMock->method('getEngineName')
            ->willReturn('search_engine_name');

        $this->locatorMock = self::createMock(ServiceLocator::class);
    }

    public function testSearchEngineIndexerInstanceReturned()
    {
        $searchEngineIndexerMock = self::createMock(IndexerInterface::class);
        $this->locatorMock->expects(self::once())
            ->method('get')
            ->with($this->engineParametersBagMock->getEngineName())
            ->willReturn($searchEngineIndexerMock);

        self::assertEquals(
            $searchEngineIndexerMock,
            SearchEngineIndexerFactory::create($this->locatorMock, $this->engineParametersBagMock)
        );
    }

    /**
     * @dataProvider wrongEngineIndexerInstancesProvider
     */
    public function testWrongSearchEngineIndexerInstanceTypeReturned($engine)
    {
        $this->locatorMock->expects(self::once())
            ->method('get')
            ->with($this->engineParametersBagMock->getEngineName())
            ->willReturn($engine);

        $this->expectException(UnexpectedTypeException::class);

        SearchEngineIndexerFactory::create($this->locatorMock, $this->engineParametersBagMock);
    }

    /**
     * @return array
     */
    public function wrongEngineIndexerInstancesProvider(): array
    {
        return ['scalar' => ['test string'], 'array' => [[]], 'object' => [new \StdClass()]];
    }
}
