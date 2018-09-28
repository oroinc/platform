<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\UserBundle\Validator\Constraints\UniqueUserEmail;
use Symfony\Component\Validator\Constraint;

class UniqueUserEmailTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UniqueUserEmail
     */
    private $constraint;

    protected function setUp()
    {
        $this->constraint = new UniqueUserEmail();
    }

    public function testValidatedBy()
    {
        self::assertEquals('oro_user.validator.unique_user_email', $this->constraint->validatedBy());
    }

    public function testGetTqargets()
    {
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
