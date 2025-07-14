<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NotLessThanOriginalValue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class NotLessThanOriginalValueTest extends TestCase
{
    public function testGetRequiredOptions(): void
    {
        $constraint = new NotLessThanOriginalValue(['scope' => 'extended', 'option' => 'length']);
        self::assertEquals(['scope', 'option'], $constraint->getRequiredOptions());
    }

    public function testTryToConstructWithoutOptions(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage(
            sprintf('The options "scope", "option" must be set for constraint "%s".', NotLessThanOriginalValue::class)
        );

        new NotLessThanOriginalValue();
    }

    public function testTryToConstructWithoutScopeOption(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage(
            sprintf('The options "scope" must be set for constraint "%s".', NotLessThanOriginalValue::class)
        );

        new NotLessThanOriginalValue(['option' => 'length']);
    }

    public function testTryToConstructWithoutOptionNameOption(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage(
            sprintf('The options "option" must be set for constraint "%s".', NotLessThanOriginalValue::class)
        );

        new NotLessThanOriginalValue(['scope' => 'extended']);
    }
}
