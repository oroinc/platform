<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Oro\Bundle\EntityBundle\ORM\MappingDriverChain;

class MappingDriverChainTest extends \PHPUnit\Framework\TestCase
{
    /** @var MappingDriverChain */
    private $chain;

    protected function setUp(): void
    {
        $this->chain = new MappingDriverChain();
    }

    public function testTransientCache()
    {
        $mappingDriver = $this->createMock(MappingDriver::class);
        $mappingDriver->expects($this->once())->method('isTransient')->willReturn(true);

        $this->chain->addDriver($mappingDriver, \stdClass::class);

        $this->chain->isTransient(\stdClass::class);
        $this->chain->isTransient(\stdClass::class);
    }
}
