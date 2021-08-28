<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EmailBundle\Validator\Constraints\EmailTemplateSyntax;
use Symfony\Component\Validator\Constraint;

class EmailTemplateSyntaxTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailTemplateSyntax */
    private $constraint;

    protected function setUp(): void
    {
        $this->constraint = new EmailTemplateSyntax();
    }

    public function testConfiguration()
    {
        $this->assertEquals('oro_email.email_template_syntax_validator', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
