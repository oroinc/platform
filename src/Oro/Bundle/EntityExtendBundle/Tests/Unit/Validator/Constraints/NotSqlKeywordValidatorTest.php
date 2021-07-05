<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NotSqlKeyword;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NotSqlKeywordValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NotSqlKeywordValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        $connection = $this->createMock(Connection::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        return new NotSqlKeywordValidator($doctrine);
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(string $value, bool $valid)
    {
        $constraint = new NotSqlKeyword();
        $this->validator->validate($value, $constraint);

        if ($valid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->assertRaised();
        }
    }

    public function validateDataProvider(): array
    {
        return [
            ['', true],
            ['test', true],
            ['select', false],
            ['SELECT', false],
        ];
    }
}
