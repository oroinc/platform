<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Filter\FeatureCheckerWorkflowRegistryFilter;

class FeatureCheckerWorkflowRegistryFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testFilter()
    {
        $featureChecker = $this->getMockBuilder(FeatureChecker::class)->disableOriginalConstructor()->getMock();
        $filter = new FeatureCheckerWorkflowRegistryFilter($featureChecker);

        $collection = new ArrayCollection(
            [
                'wd1' => $wd1 = (new WorkflowDefinition())->setName('wd1'),
                'wd2' => $wd2 = (new WorkflowDefinition())->setName('wd2'),
            ]
        );

        $featureChecker->expects($this->at(0))
            ->method('isResourceEnabled')->with('wd1', 'workflows')->willReturn(false);
        $featureChecker->expects($this->at(1))
            ->method('isResourceEnabled')->with('wd2', 'workflows')->willReturn(true);

        $result = $filter->filter($collection);

        $this->assertEquals(['wd2' => $wd2], $result->toArray());
    }

    public function testFilterCachesResult()
    {
        $featureChecker = $this->getMockBuilder(FeatureChecker::class)->disableOriginalConstructor()->getMock();
        $filter = new FeatureCheckerWorkflowRegistryFilter($featureChecker);

        $collection = new ArrayCollection(
            [
                'wd1' => $wd1 = (new WorkflowDefinition())->setName('wd1'),
                'wd2' => $wd2 = (new WorkflowDefinition())->setName('wd2'),
            ]
        );

        $featureChecker->expects($this->at(0))
            ->method('isResourceEnabled')->with('wd1', 'workflows')->willReturn(false);
        $featureChecker->expects($this->at(1))
            ->method('isResourceEnabled')->with('wd2', 'workflows')->willReturn(true);

        $result1 = $filter->filter($collection);
        $this->assertEquals(['wd2' => $wd2], $result1->toArray());
        $result2 = $filter->filter($collection);
        $this->assertEquals(['wd2' => $wd2], $result2->toArray());
    }
}
