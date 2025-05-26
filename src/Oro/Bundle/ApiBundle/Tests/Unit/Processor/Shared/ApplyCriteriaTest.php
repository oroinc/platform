<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\Common\Collections\Criteria as CommonCriteria;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\Shared\ApplyCriteria;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use PHPUnit\Framework\MockObject\MockObject;

class ApplyCriteriaTest extends GetListProcessorTestCase
{
    private CriteriaConnector&MockObject $criteriaConnector;
    private ApplyCriteria $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->criteriaConnector = $this->createMock(CriteriaConnector::class);

        $this->processor = new ApplyCriteria($this->criteriaConnector);
    }

    public function testProcessWhenQueryDoesNotExist(): void
    {
        $this->criteriaConnector->expects(self::never())
            ->method('applyCriteria');

        $this->context->setCriteria($this->createMock(Criteria::class));
        $this->processor->process($this->context);
    }

    public function testProcessWhenCriteriaObjectDoesNotExist(): void
    {
        $this->criteriaConnector->expects(self::never())
            ->method('applyCriteria');

        $this->context->setQuery($this->createMock(QueryBuilder::class));
        $this->processor->process($this->context);
    }

    public function testProcessWithCriteria(): void
    {
        $query = $this->createMock(QueryBuilder::class);
        $criteria = $this->createMock(Criteria::class);

        $this->criteriaConnector->expects(self::once())
            ->method('applyCriteria')
            ->with(self::identicalTo($query), self::identicalTo($criteria));

        $this->context->setQuery($query);
        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        self::assertNull($this->context->getCriteria());
    }

    public function testProcessWithCommonCriteria(): void
    {
        $query = $this->createMock(QueryBuilder::class);
        $criteria = $this->createMock(CommonCriteria::class);

        $this->criteriaConnector->expects(self::once())
            ->method('applyCriteria')
            ->with(self::identicalTo($query), self::identicalTo($criteria));

        $this->context->setQuery($query);
        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        self::assertNull($this->context->getCriteria());
    }
}
