<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Engine\EngineParameters;
use Oro\Bundle\SearchBundle\Engine\SearchEngineFactory;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SearchEngineFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var EngineParameters|\PHPUnit\Framework\MockObject\MockObject */
    private $engineParametersBag;

    /** @var ServiceLocator|\PHPUnit\Framework\MockObject\MockObject */
    private $locator;

    protected function setUp(): void
    {
        $this->engineParametersBag = $this->createMock(EngineParameters::class);
        $this->locator = $this->createMock(ServiceLocator::class);

        $this->engineParametersBag->expects($this->any())
            ->method('getEngineName')
            ->willReturn('search_engine_name');
    }

    public function testSearchEngineInstanceReturned()
    {
        $searchEngineMock = $this->createMock(EngineInterface::class);
        $this->locator->expects(self::once())
            ->method('get')
            ->with($this->engineParametersBag->getEngineName())
            ->willReturn($searchEngineMock);

        self::assertEquals(
            $searchEngineMock,
            SearchEngineFactory::create($this->locator, $this->engineParametersBag)
        );
    }

    /**
     * @dataProvider wrongEngineInstancesProvider
     */
    public function testWrongSearchEngineInstanceTypeReturned(mixed $engine)
    {
        $this->locator->expects(self::once())
            ->method('get')
            ->with($this->engineParametersBag->getEngineName())
            ->willReturn($engine);

        $this->expectException(UnexpectedTypeException::class);

        SearchEngineFactory::create($this->locator, $this->engineParametersBag);
    }

    public function wrongEngineInstancesProvider(): array
    {
        return [
            'scalar' => ['test string'],
            'array' => [[]],
            'object' => [new \stdClass()]
        ];
    }
}
