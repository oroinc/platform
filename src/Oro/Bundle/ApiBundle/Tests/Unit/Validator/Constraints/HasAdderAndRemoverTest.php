<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Validator\Constraints\HasAdderAndRemover;

class HasAdderAndRemoverTest extends \PHPUnit_Framework_TestCase
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
        $this->assertEquals(Response::HTTP_NOT_IMPLEMENTED, $constraint->getStatusCode());
    }

    public function testGetTargets()
    {
        $constraint = new HasAdderAndRemover(['class' => 'Test\Class', 'property' => 'testProperty']);
        $this->assertEquals('property', $constraint->getTargets());
    }
}
