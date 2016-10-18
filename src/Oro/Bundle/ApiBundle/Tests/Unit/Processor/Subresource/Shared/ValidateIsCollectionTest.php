<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateIsCollection;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;

class ValidateIsCollectionTest extends GetSubresourceProcessorTestCase
{
    /** @var ValidateIsCollection */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ValidateIsCollection();
    }

    public function testProcessWhenIsCollectionFlagIsTrue()
    {
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "add_relationship" action supports only a collection valued relationship. Association: Test\Class::test.
     */
    // @codingStandardsIgnoreEnd
    public function testProcessWhenIsCollectionFlagIsFalse()
    {
        $this->context->setAction(ApiActions::ADD_RELATIONSHIP);
        $this->context->setParentClassName('Test\Class');
        $this->context->setAssociationName('test');
        $this->processor->process($this->context);
    }
}
