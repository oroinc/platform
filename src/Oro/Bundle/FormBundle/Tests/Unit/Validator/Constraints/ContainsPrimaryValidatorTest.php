<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Oro\Bundle\FormBundle\Entity\PrimaryItem;
use Oro\Bundle\FormBundle\Validator\Constraints\ContainsPrimary;
use Oro\Bundle\FormBundle\Validator\Constraints\ContainsPrimaryValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ContainsPrimaryValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new ContainsPrimaryValidator();
    }

    public function testValidateException(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "array or Traversable and ArrayAccess", "bool" given'
        );

        $constraint = $this->createMock(Constraint::class);
        $this->validator->validate(false, $constraint);
    }

    public function testShouldKeepLazyCollectionUninitialized(): void
    {
        $collection = $this->getMockForAbstractClass(AbstractLazyCollection::class);
        $this->validator->validate($collection, $this->createMock(Constraint::class));

        $this->assertFalse($collection->isInitialized());
    }

    /**
     * @dataProvider validItemsDataProvider
     */
    public function testValidateValid(array $items): void
    {
        $constraint = new ContainsPrimary();
        $this->validator->validate($items, $constraint);

        $this->assertNoViolation();
    }

    public function validItemsDataProvider(): array
    {
        return [
            'no items' => [
                []
            ],
            'one item primary' => [
                [$this->getPrimaryItem(true)]
            ],
            'more than one item with primary' => [
                [$this->getPrimaryItem(false), $this->getPrimaryItem(true)]
            ],
            'empty item and primary' => [
                [
                    $this->getPrimaryItem(false),
                    $this->getPrimaryItem(true),
                    $this->getPrimaryItem(false)
                ]
            ]
        ];
    }

    /**
     * @dataProvider invalidItemsDataProvider
     */
    public function testValidateInvalid(array $items): void
    {
        $constraint = new ContainsPrimary();
        $this->validator->validate($items, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function invalidItemsDataProvider(): array
    {
        return [
            'one item' => [
                [$this->getPrimaryItem(false)]
            ],
            'more than one item no primary' => [
                [$this->getPrimaryItem(false), $this->getPrimaryItem(false)]
            ],
            'more than one item more than one primary' => [
                [$this->getPrimaryItem(true), $this->getPrimaryItem(true)]
            ],
        ];
    }

    private function getPrimaryItem(bool $isPrimary): PrimaryItem
    {
        $item = $this->createMock(PrimaryItem::class);
        $item->expects($this->any())
            ->method('isPrimary')
            ->willReturn($isPrimary);

        return $item;
    }
}
