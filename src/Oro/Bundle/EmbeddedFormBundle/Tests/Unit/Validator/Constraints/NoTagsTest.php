<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EmbeddedFormBundle\Validator\Constraints\NoTags;

class NoTagsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NoTags
     */
    protected $constraint;

    protected function setUp()
    {
        $this->constraint = new NoTags();
    }

    /**
     * @test
     */
    public function shouldReturnValidatorClass()
    {
        $this->assertEquals(
            'Oro\\Bundle\\EmbeddedFormBundle\\Validator\\Constraints\\NoTagsValidator',
            $this->constraint->validatedBy()
        );
    }

    /**
     * @test
     */
    public function shouldReturnPropertiesTarget()
    {
        $this->assertEquals(
            NoTags::PROPERTY_CONSTRAINT,
            $this->constraint->getTargets()
        );
    }
}
