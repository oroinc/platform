<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateIsCollection;
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

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage This action supports only a collection valued relationship.
     */
    public function testProcessWhenIsCollectionFlagIsFalse()
    {
        $this->processor->process($this->context);
    }
}
