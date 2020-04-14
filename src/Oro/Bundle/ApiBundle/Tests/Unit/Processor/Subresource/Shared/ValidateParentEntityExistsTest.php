<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentEntityExists;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;

class ValidateParentEntityExistsTest extends ChangeRelationshipProcessorTestCase
{
    /** @var ValidateParentEntityExists */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ValidateParentEntityExists();
    }

    public function testProcessWhenParentEntityExists()
    {
        $this->context->setParentEntity(new \stdClass());
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage The parent entity does not exist.
     */
    public function testProcessWhenParentEntityDoesNotExist()
    {
        $this->processor->process($this->context);
    }
}
