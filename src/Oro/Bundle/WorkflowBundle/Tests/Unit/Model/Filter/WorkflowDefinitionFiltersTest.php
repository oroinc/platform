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
    private RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack;

    private WorkflowDefinitionFilters $filters;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->filters = new WorkflowDefinitionFilters($this->requestStack);
        $this->filters->addFilter(new SystemDefinitionFilter());
        $this->filters->addFilter(new DefaultDefinitionFilter());
    }

    public function testGetFilters(): void
    {
        $this->requestStack->expects(self::exactly(2))
            ->method('getMainRequest')
            ->willReturn(null);

        self::assertEquals(
            new ArrayCollection([new SystemDefinitionFilter()]),
            $this->filters->getFilters()
        );
    }

    public function testGetFiltersWithRequest(): void
    {
        $this->requestStack->expects(self::exactly(2))
            ->method('getMainRequest')
            ->willReturn(new Request());

        self::assertEquals(
            new ArrayCollection([new SystemDefinitionFilter(), new DefaultDefinitionFilter()]),
            $this->filters->getFilters()
        );
    }

    public function testGetFiltersWithSystemType(): void
    {
        $this->filters->setType(WorkflowDefinitionFilters::TYPE_SYSTEM);

        $this->requestStack->expects(self::never())
            ->method('getMainRequest');

        self::assertEquals(
            new ArrayCollection([new SystemDefinitionFilter()]),
            $this->filters->getFilters()
        );
    }

    public function testGetFiltersWithDisabled(): void
    {
        $this->filters->setEnabled(false);

        $this->requestStack->expects(self::never())
            ->method('getMainRequest');

        self::assertEquals(
            new ArrayCollection(),
            $this->filters->getFilters()
        );
    }

    public function testSetEnabled(): void
    {
        $this->filters->setEnabled(true);
        self::assertTrue($this->filters->isEnabled());
        $this->filters->setEnabled(false);
        self::assertFalse($this->filters->isEnabled());
    }

    public function testSetType(): void
    {
        self::assertEquals(WorkflowDefinitionFilters::TYPE_DEFAULT, $this->filters->getType());
        $this->filters->setType(WorkflowDefinitionFilters::TYPE_SYSTEM);
        self::assertEquals(WorkflowDefinitionFilters::TYPE_SYSTEM, $this->filters->getType());
    }
}
