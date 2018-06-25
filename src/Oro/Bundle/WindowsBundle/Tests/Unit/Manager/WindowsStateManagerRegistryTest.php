<?php

namespace Oro\Bundle\WindowsBundle\Tests\Manager;

use Oro\Bundle\WindowsBundle\Manager\WindowsStateManager;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManagerRegistry;

class WindowsStateManagerRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var WindowsStateManagerRegistry */
    protected $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|WindowsStateManager */
    protected $defaultManager;

    protected function setUp()
    {
        $this->defaultManager = $this->getMockBuilder('Oro\Bundle\WindowsBundle\Manager\WindowsStateManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = new WindowsStateManagerRegistry($this->defaultManager);
    }

    public function testGetDefaultManager()
    {
        $this->defaultManager->expects($this->once())->method('isApplicable')->willReturn(true);

        $this->assertTrue($this->registry->getManager()->isApplicable());
    }

    public function testGetManager()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|WindowsStateManager $manager */
        $manager = $this->getMockBuilder('Oro\Bundle\WindowsBundle\Manager\WindowsStateManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry->addManager($manager);

        $this->defaultManager->expects($this->never())->method($this->anything());
        $manager->expects($this->exactly(2))->method('isApplicable')->willReturn(true);

        $this->assertTrue($this->registry->getManager()->isApplicable());
    }
}
