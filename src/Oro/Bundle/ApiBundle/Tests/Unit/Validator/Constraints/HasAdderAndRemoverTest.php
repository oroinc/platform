<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ApiBundle\Validator\Constraints\HasAdderAndRemover;
use Symfony\Component\HttpFoundation\Response;

class HasAdderAndRemoverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @expectedException \Symfony\Component\Validator\Exception\MissingOptionsException
     * @expectedExceptionMessage The options "class", "property" must be set
     */
    public function testRequiredOptions()
    {
        new HasAdderAndRemover();
    }

    public function testGetStatusCode()
    {
        $constraint = new HasAdderAndRemover(['class' => 'Test\Class', 'property' => 'testProperty']);
        self::assertEquals(Response::HTTP_NOT_IMPLEMENTED, $constraint->getStatusCode());
    }

    public function testGetTargets()
    {
        $constraint = new HasAdderAndRemover(['class' => 'Test\Class', 'property' => 'testProperty']);
        self::assertEquals('property', $constraint->getTargets());
    }
}
