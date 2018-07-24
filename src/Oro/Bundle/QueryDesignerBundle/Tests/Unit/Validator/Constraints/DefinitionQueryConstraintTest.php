<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\DefinitionQueryConstraint;

class DefinitionQueryConstraintTest extends \PHPUnit\Framework\TestCase
{
    /** @var DefinitionQueryConstraint */
    protected $constraint;

    protected function setUp()
    {
        $this->constraint = new DefinitionQueryConstraint();
    }

    public function testGetTargets()
    {
        $this->assertEquals('class', $this->constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $this->assertEquals('oro_query_designer.definition_query_validator', $this->constraint->validatedBy());
    }

    public function testMessages()
    {
        $this->assertEquals('oro.query_designer.not_accessible_class', $this->constraint->message);
        $this->assertEquals('oro.query_designer.not_accessible_class_column', $this->constraint->messageColumn);
    }
}
