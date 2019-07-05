<?php

/*
 * This file is a copy of {@see Symfony\Component\Validator\Tests\Constraints\AllValidatorTest}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\PlatformBundle\Validator\Constraints\ValidLoadedItems;
use Oro\Bundle\PlatformBundle\Validator\Constraints\ValidLoadedItemsValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ValidLoadedItemsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new ValidLoadedItemsValidator();
    }

    /**
     * @dataProvider getValidArguments
     */
    public function testWalkMultipleConstraints($array)
    {
        $constraint = new ValidLoadedItems();
        $constraint->constraints = [new NotBlank(), new NotNull()];

        $i = 0;
        foreach ($array as $key => $value) {
            $this->expectValidateValueAt($i++, '[' . $key . ']', $value, $constraint->constraints);
        }

        $this->validator->validate($array, $constraint);

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
        $constraint = new ValidLoadedItems();
        $constraint->constraints = [new NotBlank(), new NotNull()];

        /** @var EntityManagerInterface $em */
        $em = $this->createMock(EntityManagerInterface::class);
        /** @var ClassMetadata $class */
        $class = $this->createMock(ClassMetadata::class);
        $collection = new PersistentCollection($em, $class, new ArrayCollection([1]));

        $this->expectValidateValueAt(0, '[0]', 1, $constraint->constraints);
        $this->validator->validate($collection, $constraint);

        $this->assertNoViolation();
    }
}
