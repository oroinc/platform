<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Origin;

use Oro\Bundle\SyncBundle\Authentication\Origin\OriginProviderChain;
use Oro\Bundle\SyncBundle\Authentication\Origin\OriginProviderInterface;

class OriginProviderChainTest extends \PHPUnit\Framework\TestCase
{
    public function testGetOriginsWhenNoChildProviders()
    {
        $originProviderChain = new OriginProviderChain([]);
        self::assertSame([], $originProviderChain->getOrigins());
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

        $originProviderChain = new OriginProviderChain([$originProvider1, $originProvider2]);
        self::assertEquals(
            ['sampleOrigin1', 'sampleOrigin2', 'sampleOrigin3'],
            $originProviderChain->getOrigins()
        );
    }
}
