<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Origin;

use Oro\Bundle\SyncBundle\Authentication\Origin\OriginProviderChain;
use Oro\Bundle\SyncBundle\Authentication\Origin\OriginProviderInterface;

class OriginProviderChainTest extends \PHPUnit\Framework\TestCase
{
    /** @var OriginProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $originProvider;

    /** @var OriginProviderChain */
    private $originProviderChain;

    protected function setUp()
    {
        $this->originProvider = $this->createMock(OriginProviderInterface::class);

        $this->originProviderChain = new OriginProviderChain();
    }

    /**
     * @dataProvider getOriginsDataProvider
     *
     * @param string[] $origins
     * @param array $expectedOrigins
     */
    public function testGetOrigins(array $origins, array $expectedOrigins): void
    {
        $this->originProvider
            ->expects(self::once())
            ->method('getOrigins')
            ->willReturn($origins);

        self::assertEquals([], $this->originProviderChain->getOrigins());
        $this->originProviderChain->addProvider($this->originProvider);
        self::assertEquals($expectedOrigins, $this->originProviderChain->getOrigins());
    }

    /**
     * @return array
     */
    public function getOriginsDataProvider(): array
    {
        return [
            'normal origins' => [
                'origins' => ['sampleOrigin1', 'sampleOrigin2'],
                'expectedOrigins' => ['sampleOrigin1', 'sampleOrigin2'],
            ],
            'duplicated origin are removed' => [
                'origins' => ['sampleOrigin1', 'sampleOrigin2', 'sampleOrigin2'],
                'expectedOrigins' => ['sampleOrigin1', 'sampleOrigin2'],
            ],
        ];
    }
}
