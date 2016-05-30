<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\DeleteList\SetDeleteLimit;

class SetDeleteLimitTest extends DeleteListProcessorTestCase
{
    /** @var SetDeleteLimit */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new SetDeleteLimit();
    }

    public function testProcessWhenQueryIsAlreadyBuilt()
    {
        $this->context->setQuery(new \stdClass());

        $context = clone $this->context;
        $this->processor->process($this->context);
        $this->assertEquals($context, $this->context);
    }

    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $context = clone $this->context;
        $this->processor->process($this->context);
        $this->assertEquals($context, $this->context);
    }

    public function testProcessWhenLimitIsAlreadySet()
    {
        $maxResults = 2;

        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);
        $criteria->setMaxResults($maxResults);

        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        $this->assertEquals($maxResults, $criteria->getMaxResults());
    }

    public function testProcessWhenLimitIsRemoved()
    {
        $maxResults = -1;

        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);
        $criteria->setMaxResults($maxResults);

        $this->context->setCriteria($criteria);
        $this->processor->process($this->context);

        $this->assertEquals($maxResults, $criteria->getMaxResults());
    }

    public function testProcessWhenNoLimitInConfig()
    {
        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);

        $config = new EntityDefinitionConfig();

        $this->context->setCriteria($criteria);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $this->assertEquals(100, $criteria->getMaxResults());
    }

    public function testProcessWhenLimitExistsInConfig()
    {
        $maxResults = 2;

        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);

        $config = new EntityDefinitionConfig();
        $config->setMaxResults($maxResults);

        $this->context->setCriteria($criteria);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $this->assertEquals($maxResults, $criteria->getMaxResults());
    }

    public function testProcessWhenLimitIsRemovedByConfig()
    {
        $maxResults = -1;

        $resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);

        $config = new EntityDefinitionConfig();
        $config->setMaxResults($maxResults);

        $this->context->setCriteria($criteria);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $this->assertEquals($maxResults, $criteria->getMaxResults());
    }
}
