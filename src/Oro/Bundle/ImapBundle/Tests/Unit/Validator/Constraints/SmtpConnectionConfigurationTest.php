<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ImapBundle\Validator\Constraints\SmtpConnectionConfiguration;
use Oro\Bundle\ImapBundle\Validator\SmtpConnectionConfigurationValidator;
use Symfony\Component\Validator\Constraint;

class SmtpConnectionConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTargets(): void
    {
        self::assertEquals(
            Constraint::CLASS_CONSTRAINT,
            (new SmtpConnectionConfiguration())->getTargets()
        );
    }

    public function testValidatedBy(): void
    {
        self::assertEquals(
            SmtpConnectionConfigurationValidator::class,
            (new SmtpConnectionConfiguration())->validatedBy()
        );
    }
}
