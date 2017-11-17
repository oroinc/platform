<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\FormBundle\Validator\Constraints\UnchangeableField;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\FormBundle\Validator\Constraints\UnchangeableFieldValidator;

use Oro\Component\Testing\Validator\AbstractConstraintValidatorTest;

class UnchangeableFieldValidatorTest extends AbstractConstraintValidatorTest
{
    /** @var Query|\PHPUnit_Framework_MockObject_MockObject */
    private $query;

    /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject */
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

    public function testViolationRaisedInCaseValueHasChanged()
    {
        $constraint = new UnchangeableField();

        $this->objectIsNotNew();
        $this->prepareClassMetadata([]);

        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('old value');

        $this->validator->validate('new value', $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->assertRaised();
    }

    public function testViolationShouldNotBeRaisedForNewObjects()
    {
        $constraint = new UnchangeableField();

        $this->objectIsNew();
        $this->prepareClassMetadata([]);

        $this->query->expects($this->never())
            ->method('getSingleScalarResult');

        $this->validator->validate('new value', $constraint);

        $this->assertNoViolation();
    }

    public function testViolationShouldNotBeRaisedIfPreviousValueWasNull()
    {
        $constraint = new UnchangeableField();

        $this->objectIsNotNew();
        $this->prepareClassMetadata([]);

        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(null);

        $this->validator->validate('new value', $constraint);

        $this->assertNoViolation();
    }

    public function testViolationShouldNotBeRaisedIfValueHasNotChanged()
    {
        $constraint = new UnchangeableField();

        $this->objectIsNotNew();
        $this->prepareClassMetadata([]);

        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('new value');

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
        return new UnchangeableFieldValidator($this->doctrineHelper);
    }

    private function objectIsNotNew()
    {
        $this->classMetadata->expects($this->any())
            ->method('getIdentifierValues')
            ->willReturn(['id' => 1, 'relation' => (object)['id' => 10]]);
    }

    private function objectIsNew()
    {
        // doesn't find an identifier cause it's new object
        $this->classMetadata->expects($this->any())
            ->method('getIdentifierValues')
            ->willReturn([]);
    }

    /**
     * @param array $return
     */
    private function prepareClassMetadata(array $return)
    {
        $this->classMetadata->expects($this->any())
            ->method('getAssociationMappings')
            ->willReturn($return);
    }
}
