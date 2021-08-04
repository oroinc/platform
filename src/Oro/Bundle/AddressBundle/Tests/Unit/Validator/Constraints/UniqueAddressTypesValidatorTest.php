<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\AbstractTypedAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Validator\Constraints\UniqueAddressTypes;
use Oro\Bundle\AddressBundle\Validator\Constraints\UniqueAddressTypesValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueAddressTypesValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new UniqueAddressTypesValidator();
    }

    public function testValidateExceptionWhenInvalidArgumentType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "array or Traversable and ArrayAccess", "bool" given'
        );

        $constraint = new UniqueAddressTypes();
        $this->validator->validate(false, $constraint);
    }

    public function testValidateExceptionWhenInvalidArgumentElementType(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\AddressBundle\Entity\AbstractTypedAddress", "array" given'
        );

        $constraint = new UniqueAddressTypes();
        $this->validator->validate([1], $constraint);
    }

    /**
     * @dataProvider validAddressesDataProvider
     */
    public function testValidateValid(array $addresses): void
    {
        $constraint = new UniqueAddressTypes();
        $this->validator->validate($addresses, $constraint);

        $this->assertNoViolation();
    }

    public function validAddressesDataProvider(): array
    {
        return [
            'no addresses' => [
                []
            ],
            'one address without type' => [
                [$this->getTypedAddress([])]
            ],
            'one address with type' => [
                [$this->getTypedAddress(['billing' => 'billing label'])]
            ],
            'many addresses unique types' => [
                [
                    $this->getTypedAddress(['billing' => 'billing label']),
                    $this->getTypedAddress(['shipping' => 'shipping label']),
                    $this->getTypedAddress(['billing_corporate' => 'billing_corporate label']),
                    $this->getTypedAddress([]),
                ]
            ],
            'empty address' => [
                [
                    $this->getTypedAddress(['billing' => 'billing label']),
                    $this->getTypedAddress(['shipping' => 'shipping label']),
                    $this->getTypedAddress([], true),
                ]
            ]
        ];
    }

    /**
     * @dataProvider invalidAddressesDataProvider
     */
    public function testValidateInvalid(array $addresses, string $types): void
    {
        $constraint = new UniqueAddressTypes();
        $this->validator->validate($addresses, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ types }}', $types)
            ->assertRaised();
    }

    public function invalidAddressesDataProvider(): array
    {
        return [
            'several addresses with one same type' => [
                [
                    $this->getTypedAddress(['billing' => 'billing label']),
                    $this->getTypedAddress(['billing' => 'billing label', 'shipping' => 'shipping label']),
                ],
                '"billing label"'
            ],
            'several addresses with two same types' => [
                [
                    $this->getTypedAddress(['billing' => 'billing label']),
                    $this->getTypedAddress(['shipping' => 'shipping label']),
                    $this->getTypedAddress(['billing' => 'billing label', 'shipping' => 'shipping label']),
                ],
                '"billing label", "shipping label"'
            ],
        ];
    }

    private function getTypedAddress(array $addressTypes, bool $isEmpty = false): AbstractTypedAddress
    {
        $addressTypeEntities = [];
        foreach ($addressTypes as $name => $label) {
            $addressType = new AddressType($name);
            $addressType->setLabel($label);
            $addressTypeEntities[] = $addressType;
        }

        $address = $this->createMock(AbstractTypedAddress::class);
        $address->expects($this->any())
            ->method('getTypes')
            ->willReturn($addressTypeEntities);
        $address->expects($this->once())
            ->method('isEmpty')
            ->willReturn($isEmpty);

        return $address;
    }
}
