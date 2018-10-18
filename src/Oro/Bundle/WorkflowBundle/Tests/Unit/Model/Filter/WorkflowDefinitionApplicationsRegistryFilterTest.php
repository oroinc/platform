<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionApplicationsRegistryFilter;

class WorkflowDefinitionApplicationsRegistryFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var CurrentApplicationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $currentApplicationProvider;

    /** @var WorkflowDefinitionApplicationsRegistryFilter */
    private $filter;

    protected function setUp()
    {
        $this->currentApplicationProvider = $this->createMock(CurrentApplicationProviderInterface::class);

        $this->filter = new WorkflowDefinitionApplicationsRegistryFilter($this->currentApplicationProvider);
    }

    /**
     * @dataProvider filterDataProvider
     *
     * @param ArrayCollection $definitions
     * @param string $currentApplication
     * @param array $expected
     */
    public function testFilter(ArrayCollection $definitions, $currentApplication, array $expected)
    {
        $this->currentApplicationProvider->expects($this->once())
            ->method('getCurrentApplication')
            ->willReturn($currentApplication);

        $this->assertEquals($expected, $this->filter->filter($definitions)->toArray());
    }

    /**
     * @return array
     */
    public function filterDataProvider()
    {
        $wd1 = (new WorkflowDefinition)->setName('wd1')->setApplications(['default']);
        $wd2 = (new WorkflowDefinition)->setName('wd2')->setApplications(['default', 'commerce']);
        $wd3 = (new WorkflowDefinition)->setName('wd3');
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
