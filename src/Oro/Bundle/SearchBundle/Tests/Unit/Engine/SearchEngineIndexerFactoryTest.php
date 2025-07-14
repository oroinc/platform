<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Engine\EngineParameters;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Engine\SearchEngineIndexerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SearchEngineIndexerFactoryTest extends TestCase
{
    private EngineParameters&MockObject $engineParametersBag;
    private ServiceLocator&MockObject $locator;

    #[\Override]
    protected function setUp(): void
    {
        $this->engineParametersBag = $this->createMock(EngineParameters::class);
        $this->locator = $this->createMock(ServiceLocator::class);

        $this->engineParametersBag->expects($this->any())
            ->method('getEngineName')
            ->willReturn('search_engine_name');
    }

    public function testSearchEngineIndexerInstanceReturned(): void
    {
        $searchEngineIndexerMock = $this->createMock(IndexerInterface::class);
        $this->locator->expects(self::once())
            ->method('get')
            ->with($this->engineParametersBag->getEngineName())
            ->willReturn($searchEngineIndexerMock);

        self::assertEquals(
            $searchEngineIndexerMock,
            SearchEngineIndexerFactory::create($this->locator, $this->engineParametersBag)
        );
    }

    /**
     * @dataProvider wrongEngineIndexerInstancesProvider
     */
    public function testWrongSearchEngineIndexerInstanceTypeReturned(mixed $engine): void
    {
        $this->locator->expects(self::once())
            ->method('get')
            ->with($this->engineParametersBag->getEngineName())
            ->willReturn($engine);

        $this->expectException(UnexpectedTypeException::class);

        SearchEngineIndexerFactory::create($this->locator, $this->engineParametersBag);
    }

    public function wrongEngineIndexerInstancesProvider(): array
    {
        return [
            'scalar' => ['test string'],
            'array' => [[]],
            'object' => [new \stdClass()]
        ];
    }
}
