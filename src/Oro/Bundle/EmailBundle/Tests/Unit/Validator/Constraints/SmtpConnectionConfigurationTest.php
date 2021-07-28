<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EmailBundle\Validator\Constraints\SmtpConnectionConfiguration;
use Oro\Bundle\EmailBundle\Validator\SmtpConnectionConfigurationValidator;
use Symfony\Component\Validator\Constraint;

class SmtpConnectionConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTargets()
    {
        static::assertSame(
            Constraint::CLASS_CONSTRAINT,
            (new SmtpConnectionConfiguration())->getTargets()
        );
    }

    public function testValidatedBy()
    {
        static::assertSame(
            SmtpConnectionConfigurationValidator::class,
            (new SmtpConnectionConfiguration())->validatedBy()
        );
    }
}
