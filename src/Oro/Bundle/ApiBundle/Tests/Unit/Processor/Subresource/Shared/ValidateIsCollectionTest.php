<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateIsCollection;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;

class ValidateIsCollectionTest extends GetSubresourceProcessorTestCase
{
    /** @var ValidateIsCollection */
    private $processor;

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

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException
     */
    public function testProcessWhenIsCollectionFlagIsFalse()
    {
        $this->context->setAction(ApiActions::ADD_RELATIONSHIP);
        $this->context->setParentClassName('Test\Class');
        $this->context->setAssociationName('test');
        $this->processor->process($this->context);
    }
}
