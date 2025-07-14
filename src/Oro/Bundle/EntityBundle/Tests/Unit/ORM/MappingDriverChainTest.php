<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Oro\Bundle\EntityBundle\ORM\MappingDriverChain;
use PHPUnit\Framework\TestCase;

class MappingDriverChainTest extends TestCase
{
    private MappingDriverChain $chain;

    #[\Override]
    protected function setUp(): void
    {
        $this->chain = new MappingDriverChain();
    }

    public function testTransientCache(): void
    {
        $mappingDriver = $this->createMock(MappingDriver::class);
        $mappingDriver->expects($this->once())
            ->method('isTransient')
            ->willReturn(true);

        $this->chain->addDriver($mappingDriver, \stdClass::class);

        $this->chain->isTransient(\stdClass::class);
        $this->chain->isTransient(\stdClass::class);
    }
}
