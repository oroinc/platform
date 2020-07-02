<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Origin;

use Oro\Bundle\SyncBundle\Authentication\Origin\ChainOriginProvider;
use Oro\Bundle\SyncBundle\Authentication\Origin\OriginProviderInterface;

class ChainOriginProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetOriginsWhenNoChildProviders()
    {
        $chainOriginProvider = new ChainOriginProvider([]);
        self::assertSame([], $chainOriginProvider->getOrigins());
    }

    public function testGetOrigins()
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
