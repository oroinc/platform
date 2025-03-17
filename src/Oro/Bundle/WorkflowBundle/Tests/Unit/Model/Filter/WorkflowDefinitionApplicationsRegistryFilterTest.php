<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionApplicationsRegistryFilter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkflowDefinitionApplicationsRegistryFilterTest extends TestCase
{
    private CurrentApplicationProviderInterface&MockObject $currentApplicationProvider;
    private WorkflowDefinitionApplicationsRegistryFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->currentApplicationProvider = $this->createMock(CurrentApplicationProviderInterface::class);

        $this->filter = new WorkflowDefinitionApplicationsRegistryFilter($this->currentApplicationProvider);
    }

    /**
     * @dataProvider filterDataProvider
     */
    public function testFilter(ArrayCollection $definitions, ?string $currentApplication, array $expected): void
    {
        $this->currentApplicationProvider->expects(self::once())
            ->method('getCurrentApplication')
            ->willReturn($currentApplication);

        self::assertEquals($expected, $this->filter->filter($definitions)->toArray());
    }

    public function filterDataProvider(): array
    {
        $wd1 = (new WorkflowDefinition())->setName('wd1')->setApplications(['default']);
        $wd2 = (new WorkflowDefinition())->setName('wd2')->setApplications(['default', 'commerce']);
        $wd3 = (new WorkflowDefinition())->setName('wd3');
        $definitions = new ArrayCollection(['wd1' => $wd1, 'wd2' => $wd2, 'wd3' => $wd3]);

        return [
            'no application' => [
                'definitions' => clone $definitions,
                'currentApplication' => null,
                'expected' => []
            ],
            'default application' => [
                'definitions' => clone $definitions,
                'currentApplication' => 'default',
                'expected' => ['wd1' => $wd1, 'wd2' => $wd2, 'wd3' => $wd3]
            ],
            'not default application' => [
                'definitions' => clone $definitions,
                'currentApplication' => 'commerce',
                'expected' => ['wd2' => $wd2]
            ],
            'not matched application' => [
                'definitions' => clone $definitions,
                'currentApplication' => 'not_matched_app',
                'expected' => []
            ],
        ];
    }
}
