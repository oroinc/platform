<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NotLessThanOriginalValue;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class NotLessThanOriginalValueTest extends \PHPUnit\Framework\TestCase
{
    public function testGetRequiredOptions()
    {
        $constraint = new NotLessThanOriginalValue(['scope' => 'extended', 'option' => 'length']);
        self::assertEquals(['scope', 'option'], $constraint->getRequiredOptions());
    }

    public function testTryToConstructWithoutOptions()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage(
            sprintf('The options "scope", "option" must be set for constraint "%s".', NotLessThanOriginalValue::class)
        );

        new NotLessThanOriginalValue();
    }

    public function testTryToConstructWithoutScopeOption()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage(
            sprintf('The options "scope" must be set for constraint "%s".', NotLessThanOriginalValue::class)
        );

        new NotLessThanOriginalValue(['option' => 'length']);
    }

    public function testTryToConstructWithoutOptionNameOption()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage(
            sprintf('The options "option" must be set for constraint "%s".', NotLessThanOriginalValue::class)
        );

        new NotLessThanOriginalValue(['scope' => 'extended']);
    }
}
