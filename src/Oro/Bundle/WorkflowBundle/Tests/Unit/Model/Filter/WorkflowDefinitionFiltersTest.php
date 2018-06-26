<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionFilters;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Filter\Stub\DefaultDefinitionFilter;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Filter\Stub\SystemDefinitionFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WorkflowDefinitionFiltersTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    /** @var WorkflowDefinitionFilters */
    protected $filters;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()->getMock();

        $this->filters = new WorkflowDefinitionFilters($this->requestStack);

        $this->filters->addFilter(new SystemDefinitionFilter());
        $this->filters->addFilter(new DefaultDefinitionFilter());
    }

    public function testGetFilters()
    {
        $this->requestStack->expects($this->exactly(2))->method('getMasterRequest')->willReturn(null);

        $this->assertEquals(
            new ArrayCollection([new SystemDefinitionFilter()]),
            $this->filters->getFilters()
        );
    }

    public function testGetFiltersWithRequest()
    {
        $this->requestStack->expects($this->exactly(2))->method('getMasterRequest')->willReturn(new Request());

        $this->assertEquals(
            new ArrayCollection([new SystemDefinitionFilter(), new DefaultDefinitionFilter()]),
            $this->filters->getFilters()
        );
    }

    public function testGetFiltersWithSystemType()
    {
        $this->filters->setType(WorkflowDefinitionFilters::TYPE_SYSTEM);

        $this->requestStack->expects($this->never())->method('getMasterRequest');

        $this->assertEquals(
            new ArrayCollection([new SystemDefinitionFilter()]),
            $this->filters->getFilters()
        );
    }

    public function testGetFiltersWithDisabled()
    {
        $this->filters->setEnabled(false);

        $this->requestStack->expects($this->never())->method('getMasterRequest');

        $this->assertEquals(
            new ArrayCollection(),
            $this->filters->getFilters()
        );
    }

    public function testSetEnabled()
    {
        $this->filters->setEnabled(true);
        $this->assertTrue($this->filters->isEnabled());
        $this->filters->setEnabled(false);
        $this->assertFalse($this->filters->isEnabled());
    }

    public function testSetType()
    {
        $this->assertEquals(WorkflowDefinitionFilters::TYPE_DEFAULT, $this->filters->getType());
        $this->filters->setType(WorkflowDefinitionFilters::TYPE_SYSTEM);
        $this->assertEquals(WorkflowDefinitionFilters::TYPE_SYSTEM, $this->filters->getType());
    }
}
