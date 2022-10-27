<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\DeleteList\SetDeleteLimit;

class SetDeleteLimitTest extends DeleteListProcessorTestCase
{
    private const MAX_DELETE_ENTITIES_LIMIT = 100;

    /** @var SetDeleteLimit */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetDeleteLimit(self::MAX_DELETE_ENTITIES_LIMIT);
    }

    public function testProcessWhenQueryIsAlreadyBuilt()
    {
        $this->context->setQuery(new \stdClass());

        $context = clone $this->context;
        $this->processor->process($this->context);
        self::assertEquals($context, $this->context);
    }

    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $context = clone $this->context;
        $this->processor->process($this->context);
        self::assertEquals($context, $this->context);
    }

    public function testProcessWhenLimitIsAlreadySet()
    {
        $maxResults = 2;

        $criteria = new Criteria();
        $criteria->setMaxResults($maxResults);

        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        self::assertSame($maxResults, $criteria->getMaxResults());
    }

    public function testProcessWhenLimitIsRemoved()
    {
        $maxResults = -1;

        $criteria = new Criteria();
        $criteria->setMaxResults($maxResults);

        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        self::assertSame($maxResults, $criteria->getMaxResults());
    }

    public function testProcessWhenNoLimitInConfig()
    {
        $criteria = new Criteria();

        $config = new EntityDefinitionConfig();

        $this->context->setCriteria($criteria);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertSame(self::MAX_DELETE_ENTITIES_LIMIT, $criteria->getMaxResults());
    }

    public function testProcessWhenLimitExistsInConfig()
    {
        $maxResults = 2;

        $criteria = new Criteria();

        $config = new EntityDefinitionConfig();
        $config->setMaxResults($maxResults);

        $this->context->setCriteria($criteria);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertSame($maxResults, $criteria->getMaxResults());
    }

    public function testProcessWhenLimitIsRemovedByConfig()
    {
        $criteria = new Criteria();

        $config = new EntityDefinitionConfig();
        $config->setMaxResults(-1);

        $this->context->setCriteria($criteria);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertNull($criteria->getFirstResult());
        self::assertNull($criteria->getMaxResults());
    }
}
