<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\UserBundle\Validator\Constraints\UniqueRole;

class UniqueRoleTest extends \PHPUnit_Framework_TestCase
{
    /** @var VariablesConstraint */
    protected $constraint;

    protected function setUp()
    {
        $this->constraint = new UniqueRole();
    }

    protected function tearDown()
    {
        unset($this->constraint);
    }

    public function testConfiguration()
    {
        $this->assertEquals('oro_user.unique_role', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
