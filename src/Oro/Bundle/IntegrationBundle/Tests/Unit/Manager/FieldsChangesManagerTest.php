<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\FieldsChanges;
use Oro\Bundle\IntegrationBundle\Manager\FieldsChangesManager;

class FieldsChangesManagerTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = \stdClass::class;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repo;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var FieldsChangesManager */
    private $manager;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(EntityRepository::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturn(self::CLASS_NAME);
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturn(1);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->doctrineHelper->expects($this->any())
            ->method('createEntityInstance')
            ->willReturn(new FieldsChanges([]));

        $this->em->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repo);

        $this->manager = new FieldsChangesManager($this->doctrineHelper, self::CLASS_NAME);
    }

    /**
     * @dataProvider getChangesDataProvider
     */
    public function testGetChanges(?FieldsChanges $fieldsChanges, array $expected, bool $doRemove)
    {
        if ($fieldsChanges) {
            $this->repo->expects($this->any())
                ->method('findOneBy')
                ->with($this->isType('array'))
                ->willReturn($fieldsChanges);
        } else {
            $newFieldsChanges = new FieldsChanges([]);
            $newFieldsChanges
                ->setEntityClass(self::CLASS_NAME)
                ->setEntityId(1);

            $this->doctrineHelper->expects($this->once())
                ->method('createEntityInstance')
                ->willReturn($newFieldsChanges);

            $this->em->expects($this->once())
                ->method('persist')
                ->with($this->equalTo($newFieldsChanges));
        }

        if ($doRemove) {
            $this->em->expects($this->once())
                ->method('remove')
                ->with($this->equalTo($fieldsChanges));
        }

        $this->assertEquals(
            $expected,
            $this->manager->getChanges(new \stdClass(), $doRemove)
        );
    }

    public function getChangesDataProvider(): array
    {
        return [
            [new FieldsChanges([]), [], false],
            [new FieldsChanges(['field']), ['field'], false],
            [null, [], false],
            [new FieldsChanges([]), [], true],
            [new FieldsChanges(['field']), ['field'], true],
            [null, [], true]
        ];
    }

    /**
     * @dataProvider setChangesDataProvider
     */
    public function testSetChanges(?FieldsChanges $fieldsChanges, array $expected)
    {
        $entity = new \stdClass();
        $this->repo->expects($this->any())
            ->method('findOneBy')
            ->with($this->isType('array'))
            ->willReturn($fieldsChanges);

        $fieldsChanges = $this->manager->setChanges($entity, $expected);

        $this->assertEquals(
            $expected,
            $fieldsChanges->getChangedFields()
        );
    }

    public function setChangesDataProvider(): array
    {
        return [
            [new FieldsChanges(['field']), ['field']],
            [new FieldsChanges([]), []],
            [null, []],
        ];
    }

    public function testRemoveChanges()
    {
        $entity = new \stdClass();
        $fieldsChanges = new FieldsChanges([]);

        $this->repo->expects($this->any())
            ->method('findOneBy')
            ->with($this->isType('array'))
            ->willReturn($fieldsChanges);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($fieldsChanges));

        $this->manager->removeChanges($entity);
    }
}
