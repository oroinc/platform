<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Oro\Bundle\EntityBundle\ORM\MappingDriverChain;

class MappingDriverChainTest extends \PHPUnit_Framework_TestCase
{
    /** @var MappingDriverChain */
    protected $chain;

    protected function setUp()
    {
        $this->chain = new MappingDriverChain();
    }

    public function testTransientCache()
    {
        /** @var MappingDriver|\PHPUnit_Framework_MockObject_MockObject $mappingDriver */
        $mappingDriver = $this->getMock('Doctrine\Common\Persistence\Mapping\Driver\MappingDriver');
        $mappingDriver->expects($this->once())->method('isTransient')->willReturn(true);

        $this->chain->addDriver($mappingDriver, '\stdClass');

        $this->chain->isTransient('\stdClass');
        $this->chain->isTransient('\stdClass');
    }
}
