<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\FormBundle\Validator\Constraints\Unchangeable;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\FormBundle\Validator\Constraints\UnchangeableValidator;

use Oro\Component\Testing\Validator\AbstractConstraintValidatorTest;

class UnchangeableValidatorTest extends AbstractConstraintValidatorTest
{
    /** @var Query|\PHPUnit_Framework_MockObject_MockObject */
    private $query;

    /** @var ClassMetadata */
    private $classMetadata;

    /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $repository;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    private $manager;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    protected function setUp()
    {
        $this->mockDoctrine();
        parent::setUp();
    }

    public function testDoesNotValidatesIfValueChanged()
    {
        $constraint = new Unchangeable();

        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(['o_' => 'old value']);

        $this->validator->validate('new value', $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->assertRaised();
    }

    public function testValidatesIfObjectIsNew()
    {
        $constraint = new Unchangeable();

        $this->validator->validate('new value', $constraint);

        $this->assertNoViolation();
    }

    public function testValidatesIfPreviousValueWasNull()
    {
        $constraint = new Unchangeable();

        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(['o_' => null]);

        $this->validator->validate('new value', $constraint);

        $this->assertNoViolation();
    }

    public function testValidatesIfValueHasNotChanged()
    {
        $constraint = new Unchangeable();

        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(['o_' => 'new value']);

        $this->validator->validate('new value', $constraint);

        $this->assertNoViolation();
    }

    private function mockDoctrine()
    {
        $this->query = $this->createMock(AbstractQuery::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->manager = $this->createMock(ObjectManager::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $queryBuilder->expects($this->any())
            ->method('select')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('where')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->repository->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->classMetadata->expects($this->any())
            ->method('getIdentifierValues')
            ->willReturn(['id' => 1]);

        $this->manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->manager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($this->classMetadata);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->willReturn($this->manager);
    }

    protected function createValidator()
    {
        return new UnchangeableValidator($this->doctrineHelper);
    }
}
