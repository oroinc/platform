<?php

/*
 * This file is a copy of {@see Symfony\Component\Validator\Tests\Constraints\AllValidatorTest}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Oro\Bundle\ApiBundle\Validator\Constraints\All;
use Oro\Bundle\ApiBundle\Validator\Constraints\AllValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AllValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new AllValidator();
    }

    public function testNullIsValid()
    {
        $this->validator->validate(null, new All(new NotBlank()));

        $this->assertNoViolation();
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testThrowsExceptionIfNotTraversable()
    {
        $this->validator->validate('test', new All(new NotBlank()));
    }

    /**
     * @dataProvider getValidArguments
     */
    public function testWalkSingleConstraint($array)
    {
        $constraint = new NotBlank();

        $i = 0;
        foreach ($array as $key => $value) {
            $this->expectValidateValueAt($i++, '[' . $key . ']', $value, [$constraint]);
        }

        $this->validator->validate($array, new All($constraint));

        $this->assertNoViolation();
    }

    /**
     * @dataProvider getValidArguments
     */
    public function testWalkMultipleConstraints($array)
    {
        $constraints = [new NotBlank(), new NotNull()];

        $i = 0;
        foreach ($array as $key => $value) {
            $this->expectValidateValueAt($i++, '[' . $key . ']', $value, $constraints);
        }

        $this->validator->validate($array, new All($constraints));

        $this->assertNoViolation();
    }

    public function getValidArguments()
    {
        return [
            [[5, 6, 7]],
            [new \ArrayObject([5, 6, 7])]
        ];
    }

    public function testShouldKeepLazyCollectionUninitialized()
    {
        /** @var AbstractLazyCollection $collection */
        $collection = $this->getMockForAbstractClass(AbstractLazyCollection::class);

        $this->validator->validate($collection, new All(new NotBlank()));

        $this->assertNoViolation();
        self::assertFalse($collection->isInitialized());
    }
}
