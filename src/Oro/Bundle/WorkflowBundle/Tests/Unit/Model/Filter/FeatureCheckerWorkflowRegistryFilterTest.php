<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Filter\FeatureCheckerWorkflowRegistryFilter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeatureCheckerWorkflowRegistryFilterTest extends TestCase
{
    private FeatureChecker&MockObject $featureChecker;
    private FeatureCheckerWorkflowRegistryFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->filter = new FeatureCheckerWorkflowRegistryFilter($this->featureChecker);
    }

    public function testFilter(): void
    {
        $wd1 = (new WorkflowDefinition())->setName('wd1');
        $wd2 = (new WorkflowDefinition())->setName('wd2');
        $collection = new ArrayCollection(['wd1' => $wd1, 'wd2' => $wd2]);

        $this->featureChecker->expects(self::exactly(2))
            ->method('isResourceEnabled')
            ->willReturnMap([
                ['wd1', 'workflows', null, false],
                ['wd2', 'workflows', null, true]
            ]);

        $result = $this->filter->filter($collection);

        self::assertEquals(['wd2' => $wd2], $result->toArray());
    }

    public function testFilterCachesResult(): void
    {
        $wd1 = (new WorkflowDefinition())->setName('wd1');
        $wd2 = (new WorkflowDefinition())->setName('wd2');
        $collection = new ArrayCollection(['wd1' => $wd1, 'wd2' => $wd2]);

        $this->featureChecker->expects(self::exactly(2))
            ->method('isResourceEnabled')
            ->willReturnMap([
                ['wd1', 'workflows', null, false],
                ['wd2', 'workflows', null, true]
            ]);

        $result1 = $this->filter->filter($collection);
        self::assertEquals(['wd2' => $wd2], $result1->toArray());
        $result2 = $this->filter->filter($collection);
        self::assertEquals(['wd2' => $wd2], $result2->toArray());
    }
}
