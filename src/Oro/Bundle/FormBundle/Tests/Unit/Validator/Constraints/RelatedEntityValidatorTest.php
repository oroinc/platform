<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\FormBundle\Validator\Constraints\RelatedEntity;
use Oro\Bundle\FormBundle\Validator\Constraints\RelatedEntityValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class RelatedEntityValidatorTest extends ConstraintValidatorTestCase
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
        return new RelatedEntityValidator($this->doctrineHelper, $this->entityClassNameHelper);
    }

    /**
     * @dataProvider validItemsDataProvider
     */
    public function testValidateValid($value)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->with($value)
            ->willReturn(true);

        $constraint = new RelatedEntity();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function validItemsDataProvider(): array
    {
        return [
            [null],
            [''],
            [[]],
            [['id' => 123]],
            [['entity' => 'Test\Entity']],
            [new \stdClass()]
        ];
    }

    public function testValidateValidAlias()
    {
        $value = ['id' => 123, 'entity' => 'alias'];

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->willReturn('Test\Entity');
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with('Test\Entity')
            ->willReturn(true);

        $constraint = new RelatedEntity();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '[id => 123, entity => "alias"]')
            ->assertRaised();
    }

    public function testValidateValidClass()
    {
        $value = ['id' => 123, 'entity' => 'Test\Entity'];

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->willReturn($value['entity']);
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with('Test\Entity')
            ->willReturn(true);

        $constraint = new RelatedEntity();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '[id => 123, entity => "Test\Entity"]')
            ->assertRaised();
    }

    public function testValidateUnknownAlias()
    {
        $value = ['id' => 123, 'entity' => 'alias'];

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->willThrowException(new EntityAliasNotFoundException());
        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntity');

        $constraint = new RelatedEntity();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateNotManageableEntity()
    {
        $value = ['id' => 123, 'entity' => 'Test\Entity'];

        $this->entityClassNameHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($value['entity'])
            ->willReturn($value['entity']);
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with($value['entity'])
            ->willReturn(false);

        $constraint = new RelatedEntity();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }
}
