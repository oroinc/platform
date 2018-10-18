<?php
namespace Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper;

trait MockHelperTrait
{
    /**
     * @param string $className
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createConstructorLessMock($className)
    {
        return $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
