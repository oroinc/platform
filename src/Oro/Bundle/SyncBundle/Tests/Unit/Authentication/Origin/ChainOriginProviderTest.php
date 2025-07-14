<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Origin;

use Oro\Bundle\SyncBundle\Authentication\Origin\ChainOriginProvider;
use Oro\Bundle\SyncBundle\Authentication\Origin\OriginProviderInterface;
use PHPUnit\Framework\TestCase;

class ChainOriginProviderTest extends TestCase
{
    public function testGetOriginsWhenNoChildProviders(): void
    {
        $chainOriginProvider = new ChainOriginProvider([]);
        self::assertSame([], $chainOriginProvider->getOrigins());
    }

    public function testGetOrigins(): void
    {
        $originProvider1 = $this->createMock(OriginProviderInterface::class);
        $originProvider1->expects(self::once())
            ->method('getOrigins')
            ->willReturn(['sampleOrigin1', 'sampleOrigin2']);
        $originProvider2 = $this->createMock(OriginProviderInterface::class);
        $originProvider2->expects(self::once())
            ->method('getOrigins')
            ->willReturn(['sampleOrigin2', 'sampleOrigin3']);

        $chainOriginProvider = new ChainOriginProvider([$originProvider1, $originProvider2]);
        self::assertEquals(
            ['sampleOrigin1', 'sampleOrigin2', 'sampleOrigin3'],
            $chainOriginProvider->getOrigins()
        );
    }
}
