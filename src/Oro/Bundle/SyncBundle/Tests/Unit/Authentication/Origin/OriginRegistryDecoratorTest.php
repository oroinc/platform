<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Origin;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\OriginRegistry;
use Oro\Bundle\SyncBundle\Authentication\Origin\OriginProviderInterface;
use Oro\Bundle\SyncBundle\Authentication\Origin\OriginRegistryDecorator;

class OriginRegistryDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var OriginProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $originProvider;

    /** @var OriginRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $baseOriginRegistry;

    /** @var OriginRegistryDecorator|\PHPUnit\Framework\MockObject\MockObject */
    private $originRegistryDecorator;

    protected function setUp()
    {
        $this->originProvider = $this->createMock(OriginProviderInterface::class);
        $this->baseOriginRegistry = $this->createMock(OriginRegistry::class);

        $this->originRegistryDecorator = new OriginRegistryDecorator($this->baseOriginRegistry, $this->originProvider);
    }

    /**
     * @dataProvider getOriginsDataProvider
     *
     * @param string[] $origins
     * @param array $baseOrigins
     * @param array $expectedOrigins
     */
    public function testGetOrigins(array $origins, array $baseOrigins, array $expectedOrigins): void
    {
        $this->originProvider
            ->expects(self::once())
            ->method('getOrigins')
            ->willReturn($origins);

        $this->baseOriginRegistry
            ->expects(self::once())
            ->method('getOrigins')
            ->willReturn($baseOrigins);

        self::assertEquals($expectedOrigins, $this->originRegistryDecorator->getOrigins());
    }

    /**
     * @return array
     */
    public function getOriginsDataProvider(): array
    {
        return [
            'ensure origins are merged' => [
                'origins' => ['sampleOrigin1', 'sampleOrigin2'],
                'baseOrigins' => ['sampleOrigin3', 'sampleOrigin4'],
                'expectedOrigins' => ['sampleOrigin3', 'sampleOrigin4', 'sampleOrigin1', 'sampleOrigin2'],
            ],
            'ensure duplicated origin are removed' => [
                'origins' => ['sampleOrigin1', 'sampleOrigin2'],
                'baseOrigins' => ['sampleOrigin2', 'sampleOrigin3'],
                'expectedOrigins' => ['sampleOrigin2', 'sampleOrigin3', 'sampleOrigin1'],
            ],
        ];
    }
}
