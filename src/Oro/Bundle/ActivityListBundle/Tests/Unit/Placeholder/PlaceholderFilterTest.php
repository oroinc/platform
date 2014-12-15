<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder;

use Oro\Bundle\ActivityListBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder\Fixture\TestNonActiveTarget;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder\Fixture\TestTarget;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $activityListProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var PlaceholderFilter */
    protected $filter;

    public function setUp()
    {
        $this->activityListProvider = $this
            ->getMockBuilder('Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine = $this
            ->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->activityListProvider->expects($this->any())
            ->method('getTargetEntityClasses')
            ->will($this->returnValue(['Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder\Fixture\TestTarget']));

        $doctrineHelper = new DoctrineHelper($this->doctrine);

        $this->filter = new PlaceholderFilter($this->activityListProvider, $this->doctrine, $doctrineHelper);
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->filter->isApplicable(new TestTarget()));
        $this->assertFalse($this->filter->isApplicable(null));
    }

    public function testIsApplicableOnNonSupportedTarget()
    {
        $repo = $this
            ->getMockBuilder('Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repo));
        $repo->expects($this->any())
            ->method('getRecordsCountForTargetClassAndId')
            ->with('Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder\Fixture\TestNonActiveTarget', 123)
            ->willReturn(true);

        $entity = new TestNonActiveTarget(123);

        $this->assertTrue($this->filter->isApplicable($entity));
    }
}
