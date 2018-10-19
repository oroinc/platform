<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\FieldNameLength;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\FieldNameLengthValidator;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class FieldNameLengthValidatorTest extends ConstraintValidatorTestCase
{
    private const STRING = 'FieldNameFieldNameFieldNameFieldNameFieldName';

    /** @var ExtendDbIdentifierNameGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $nameGenerator;

    protected function setUp()
    {
        $this->nameGenerator = $this->createMock(ExtendDbIdentifierNameGenerator::class);

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $this->constraint = new FieldNameLength();

        return parent::createContext();
    }

    /**
     * @return FieldNameLengthValidator
     */
    protected function createValidator()
    {
        return new FieldNameLengthValidator($this->nameGenerator);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testValidateException()
    {
        $this->expectExceptionMessage(
            sprintf('Expected argument of type "%s", "%s" given', FieldNameLength::class, Length::class)
        );

        $this->validator->validate(self::STRING, new Length(['min' => 1]));
    }

    /**
     * @dataProvider validateMaxLengthDataProvider
     *
     * @param string $value
     * @param bool $violation
     */
    public function testValidateMaxLength(string $value, bool $violation)
    {
        $maxLength = 22;

        $this->nameGenerator->expects($this->once())
            ->method('getMaxCustomEntityFieldNameSize')
            ->willReturn($maxLength);

        $this->validator->validate($value, $this->constraint);

        if ($violation) {
            $this->buildViolation($this->constraint->maxMessage)
                ->setParameter('{{ value }}', '"' . $value . '"')
                ->setParameter('{{ limit }}', $maxLength)
                ->setInvalidValue($value)
                ->setPlural($maxLength)
                ->setCode(FieldNameLength::TOO_LONG_ERROR)
                ->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    /**
     * @return array
     */
    public function validateMaxLengthDataProvider()
    {
        return [
            [substr(self::STRING, 0, 21), false],
            [substr(self::STRING, 0, 22), false],
            [substr(self::STRING, 0, 23), true],
        ];
    }

    /**
     * @dataProvider validateMinLengthDataProvider
     *
     * @param string $value
     * @param bool $violation
     */
    public function testValidateMinLength(string $value, bool $violation)
    {
        $minLength = 2;

        $this->validator->validate($value, $this->constraint);

        if ($violation) {
            $this->buildViolation($this->constraint->minMessage)
                ->setParameter('{{ value }}', '"' . $value . '"')
                ->setParameter('{{ limit }}', $minLength)
                ->setInvalidValue($value)
                ->setPlural($minLength)
                ->setCode(FieldNameLength::TOO_SHORT_ERROR)
                ->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    /**
     * @return array
     */
    public function validateMinLengthDataProvider()
    {
        return [
            ['A', true],
            ['AA', false],
        ];
    }
}
