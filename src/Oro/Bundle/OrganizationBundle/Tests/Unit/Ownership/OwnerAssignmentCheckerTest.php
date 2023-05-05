<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Ownership;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\OrganizationBundle\Ownership\OwnerAssignmentChecker;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Ownership\Fixture\Entity\TestEntity;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class OwnerAssignmentCheckerTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
    }

    /**
     * @dataProvider hasAssignmentsProvider
     */
    public function testHasAssignments($records, $expectedResult)
    {
        $actualSql = '';
        $statement = $this->createFetchStatementMock($records);
        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('prepare')
            ->willReturnCallback(function ($prepareString) use (&$statement, &$actualSql) {
                $actualSql = $prepareString;

                return $statement;
            });

        $checker      = new OwnerAssignmentChecker();
        $actualResult = $checker->hasAssignments(
            1,
            TestEntity::class,
            'owner',
            $this->em
        );

        $expectedSql = 'SELECT t0_.id AS id_0'
            . ' FROM TestEntity t1_'
            . ' INNER JOIN TestOwnerEntity t0_ ON t1_.owner_id = t0_.id'
            . ' WHERE t0_.id = ? LIMIT 1';
        $this->assertEquals($expectedSql, $actualSql);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function hasAssignmentsProvider(): array
    {
        return [
            [[], false],
            [[['id_0' => '1']], true]
        ];
    }
}
