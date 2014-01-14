<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\DistributionBundle\Manager\PackageManager;

class PackagesProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var PackageManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $pm;

    public function setUp()
    {
        $this->pm = $this->getMockBuilder('Oro\Bundle\DistributionBundle\Manager\PackageManager')
            ->disableOriginalConstructor()->getMock();
    }

    public function tearDown()
    {
        
    }
}
