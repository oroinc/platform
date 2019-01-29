<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validators\Constraints;

use Oro\Bundle\EmailBundle\Validator\Constraints\SmtpConnectionConfiguration;
use Oro\Bundle\EmailBundle\Validator\SmtpConnectionConfigurationValidator;
use Symfony\Component\Validator\Constraint;

class SmtpConnectionConfigurationTest extends \PHPUnit_Framework_TestCase
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
