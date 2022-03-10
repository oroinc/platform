<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ApiBundle\Validator\Constraints\HasAdderAndRemover;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class HasAdderAndRemoverTest extends \PHPUnit\Framework\TestCase
{
    public function testRequiredOptions()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The options "class", "property" must be set');

        new HasAdderAndRemover();
    }

    public function testGetStatusCode()
    {
        $constraint = new HasAdderAndRemover(['class' => 'Test\Class', 'property' => 'testProperty']);
        self::assertEquals(Response::HTTP_NOT_IMPLEMENTED, $constraint->getStatusCode());
    }
}
