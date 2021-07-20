<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizePaging;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class NormalizePagingTest extends GetListProcessorTestCase
{
    private function getProcessor(int $maxEntitiesLimit): NormalizePaging
    {
        return new NormalizePaging($maxEntitiesLimit);
    }

    public function testProcessWhenQueryIsAlreadyBuilt()
    {
        $this->context->setQuery(new \stdClass());
        $context = clone $this->context;
        $processor = $this->getProcessor(-1);
        $processor->process($this->context);
        self::assertEquals($context, $this->context);
    }

    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $processor = $this->getProcessor(-1);
        $processor->process($this->context);

        self::assertNull($this->context->getCriteria());
    }

    public function testProcessOnDisabledPaging()
    {
        $criteria = new Criteria();
        $criteria->setFirstResult(12);
        $criteria->setMaxResults(-1);

        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setCriteria($criteria);
        $processor = $this->getProcessor(-1);
        $processor->process($this->context);

        self::assertNull($criteria->getMaxResults());
        self::assertNull($criteria->getFirstResult());
    }

    public function testProcess()
    {
        $criteria = new Criteria();
        $criteria->setFirstResult(2);
        $criteria->setMaxResults(10);

        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setCriteria($criteria);
        $processor = $this->getProcessor(-1);
        $processor->process($this->context);

        self::assertSame(10, $criteria->getMaxResults());
        self::assertSame(2, $criteria->getFirstResult());
    }

    public function testProcessWhenUnlimitedMaxResults()
    {
        $criteria = new Criteria();
        $criteria->setFirstResult(2);
        $criteria->setMaxResults(10);

        $config = new EntityDefinitionConfig();
        $config->setMaxResults(-1);

        $this->context->setConfig($config);
        $this->context->setCriteria($criteria);
        $processor = $this->getProcessor(-1);
        $processor->process($this->context);

        self::assertSame(10, $criteria->getMaxResults());
        self::assertSame(2, $criteria->getFirstResult());
    }

    public function testProcessWhenMaxResultsGreaterThanRequestedPageSize()
    {
        $criteria = new Criteria();
        $criteria->setFirstResult(2);
        $criteria->setMaxResults(10);

        $config = new EntityDefinitionConfig();
        $config->setMaxResults(11);

        $this->context->setConfig($config);
        $this->context->setCriteria($criteria);
        $processor = $this->getProcessor(-1);
        $processor->process($this->context);

        self::assertSame(10, $criteria->getMaxResults());
        self::assertSame(2, $criteria->getFirstResult());
    }

    public function testProcessWhenMaxResultsLessThanRequestedPageSize()
    {
        $criteria = new Criteria();
        $criteria->setFirstResult(2);
        $criteria->setMaxResults(10);

        $config = new EntityDefinitionConfig();
        $config->setMaxResults(9);

        $this->context->setConfig($config);
        $this->context->setCriteria($criteria);
        $processor = $this->getProcessor(-1);
        $processor->process($this->context);

        self::assertSame(9, $criteria->getMaxResults());
        self::assertSame(2, $criteria->getFirstResult());
    }

    public function testProcessWithMaxEntitiesLimit()
    {
        $criteria = new Criteria();
        $criteria->setFirstResult(1);
        $criteria->setMaxResults(10);

        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setCriteria($criteria);
        $processor = $this->getProcessor(9);
        $processor->process($this->context);

        self::assertSame(9, $criteria->getMaxResults());
        self::assertSame(1, $criteria->getFirstResult());
    }

    public function testProcessWithMaxEntitiesLimitAndMaxResultsInConfig()
    {
        $criteria = new Criteria();
        $criteria->setFirstResult(1);
        $criteria->setMaxResults(10);

        $config = new EntityDefinitionConfig();
        $config->setMaxResults(11);

        $this->context->setConfig($config);
        $this->context->setCriteria($criteria);
        $processor = $this->getProcessor(12);
        $processor->process($this->context);

        self::assertSame(10, $criteria->getMaxResults());
        self::assertSame(1, $criteria->getFirstResult());
    }
}
