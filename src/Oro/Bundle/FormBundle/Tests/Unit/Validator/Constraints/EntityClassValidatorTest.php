<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\FormBundle\Validator\Constraints\EntityClass;
use Oro\Bundle\FormBundle\Validator\Constraints\EntityClassValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EntityClassValidatorTest extends ConstraintValidatorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entityClassNameHelper;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityClassNameHelper = $this->createMock(EntityClassNameHelper::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new EntityClassValidator($this->doctrineHelper, $this->entityClassNameHelper);
    }

    /**
     * @dataProvider validItemsDataProvider
     */
    public function testValidateValid(?string $value)
    {
        $this->entityClassNameHelper->expects($this->any())
            ->method('resolveEntityClass')
            ->with($value)
            ->willReturn($value);
        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->with($value)
            ->willReturn(true);

        $constraint = new EntityClass();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function validItemsDataProvider(): array
    {
        return [
            [null],
            [''],
            ['Test\Entity'],
        ];
    }

    public function testValidateValidAlias()
    {
        $value = 'alias';

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value)
            ->willReturn('Test\Entity');
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with('Test\Entity')
            ->willReturn(true);

        $constraint = new EntityClass();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateUnknownAlias()
    {
        $value = 'alias';

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value)
            ->willThrowException(new EntityAliasNotFoundException());
        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntity');

        $constraint = new EntityClass();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '"alias"')
            ->assertRaised();
    }

    public function testValidateNotManageableEntity()
    {
        $value = 'Test\Entity';

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value)
            ->willReturn($value);
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with($value)
            ->willReturn(false);

        $constraint = new EntityClass();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '"Test\Entity"')
            ->assertRaised();
    }

    public function testValidateInvalidValue()
    {
        $value = 123;

        $constraint = new EntityClass();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '123')
            ->assertRaised();
    }
}
