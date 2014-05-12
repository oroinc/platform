<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Ownership;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\OrganizationBundle\Ownership\OwnerAssignmentChecker;

class OwnerAssignmentCheckerTest extends OrmTestCase
{
    /**
     * @var EntityManagerMock
     */
    protected $em;

    protected function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Bundle\OrganizationBundle\Tests\Unit\Ownership\Fixture\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'Stub' => 'Oro\Bundle\OrganizationBundle\Tests\Unit\Ownership\Fixture\Entity'
            ]
        );
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
            ->will(
                $this->returnCallback(
                    function ($prepareString) use (&$statement, &$actualSql) {
                        $actualSql = $prepareString;

                        return $statement;
                    }
                )
            );

        $checker      = new OwnerAssignmentChecker();
        $actualResult = $checker->hasAssignments(
            1,
            'Oro\Bundle\OrganizationBundle\Tests\Unit\Ownership\Fixture\Entity\TestEntity',
            'owner',
            $this->em
        );

        $expectedSql = 'SELECT t0_.id AS id0'
            . ' FROM TestEntity t1_'
            . ' INNER JOIN TestOwnerEntity t0_ ON t1_.owner_id = t0_.id'
            . ' WHERE t0_.id = ? LIMIT 1';
        $this->assertEquals($expectedSql, $actualSql);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function hasAssignmentsProvider()
    {
        return [
            [[], false],
            [[['id0' => '1']], true]
        ];
    }
}
