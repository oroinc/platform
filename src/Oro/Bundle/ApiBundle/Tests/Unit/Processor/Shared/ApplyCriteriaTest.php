<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\Common\Collections\Criteria as CommonCriteria;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\Shared\ApplyCriteria;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;

class ApplyCriteriaTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|CriteriaConnector */
    private $criteriaConnector;

    /** @var ApplyCriteria */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->criteriaConnector = $this->createMock(CriteriaConnector::class);

        $this->processor = new ApplyCriteria($this->criteriaConnector);
    }

    public function testProcessWhenQueryDoesNotExist()
    {
        $this->criteriaConnector->expects(self::never())
            ->method('applyCriteria');

        $this->context->setCriteria($this->createMock(Criteria::class));
        $this->processor->process($this->context);
    }

    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $this->criteriaConnector->expects(self::never())
            ->method('applyCriteria');

        $this->context->setQuery($this->createMock(QueryBuilder::class));
        $this->processor->process($this->context);
    }

    public function testProcessWithCriteria()
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

    public function testProcessWithCommonCriteria()
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
