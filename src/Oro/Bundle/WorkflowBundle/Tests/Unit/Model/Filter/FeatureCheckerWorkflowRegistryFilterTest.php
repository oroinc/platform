<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Filter\FeatureCheckerWorkflowRegistryFilter;

class FeatureCheckerWorkflowRegistryFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var FeatureCheckerWorkflowRegistryFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->filter = new FeatureCheckerWorkflowRegistryFilter($this->featureChecker);
    }

    public function testFilter()
    {
        $wd1 = (new WorkflowDefinition())->setName('wd1');
        $wd2 = (new WorkflowDefinition())->setName('wd2');
        $collection = new ArrayCollection(['wd1' => $wd1, 'wd2' => $wd2]);

        $this->featureChecker->expects($this->exactly(2))
            ->method('isResourceEnabled')
            ->willReturnMap([
                ['wd1', 'workflows', null, false],
                ['wd2', 'workflows', null, true]
            ]);

        $result = $this->filter->filter($collection);

        $this->assertEquals(['wd2' => $wd2], $result->toArray());
    }

    public function testFilterCachesResult()
    {
        $wd1 = (new WorkflowDefinition())->setName('wd1');
        $wd2 = (new WorkflowDefinition())->setName('wd2');
        $collection = new ArrayCollection(['wd1' => $wd1, 'wd2' => $wd2]);

        $this->featureChecker->expects($this->exactly(2))
            ->method('isResourceEnabled')
            ->willReturnMap([
                ['wd1', 'workflows', null, false],
                ['wd2', 'workflows', null, true]
            ]);

        $result1 = $this->filter->filter($collection);
        $this->assertEquals(['wd2' => $wd2], $result1->toArray());
        $result2 = $this->filter->filter($collection);
        $this->assertEquals(['wd2' => $wd2], $result2->toArray());
    }
}
