<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Engine\EngineParameters;
use Oro\Bundle\SearchBundle\Engine\SearchEngineFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SearchEngineFactoryTest extends TestCase
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

    public function testSearchEngineInstanceReturned()
    {
        $searchEngineMock = self::createMock(EngineInterface::class);
        $this->locatorMock->expects(self::once())
            ->method('get')
            ->with($this->engineParametersBagMock->getEngineName())
            ->willReturn($searchEngineMock);

        self::assertEquals(
            $searchEngineMock,
            SearchEngineFactory::create($this->locatorMock, $this->engineParametersBagMock)
        );
    }

    /**
     * @dataProvider wrongEngineInstancesProvider
     */
    public function testWrongSearchEngineInstanceTypeReturned($engine)
    {
        $this->locatorMock->expects(self::once())
            ->method('get')
            ->with($this->engineParametersBagMock->getEngineName())
            ->willReturn($engine);

        $this->expectException(UnexpectedTypeException::class);

        SearchEngineFactory::create($this->locatorMock, $this->engineParametersBagMock);
    }

    /**
     * @return array
     */
    public function wrongEngineInstancesProvider(): array
    {
        return ['scalar' => ['test string'], 'array' => [[]], 'object' => [new \StdClass()]];
    }
}
