<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EmbeddedFormBundle\Validator\Constraints\NoTags;
use Oro\Bundle\EmbeddedFormBundle\Validator\Constraints\NoTagsValidator;
use Symfony\Component\Validator\Constraint;

class NoTagsTest extends \PHPUnit\Framework\TestCase
{
    /** @var NoTags */
    private $constraint;

    protected function setUp(): void
    {
        $this->constraint = new NoTags();
    }

    public function testShouldReturnValidatorClass()
    {
        $this->assertEquals(NoTagsValidator::class, $this->constraint->validatedBy());
    }

    public function testShouldReturnPropertiesTarget()
    {
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }
}
