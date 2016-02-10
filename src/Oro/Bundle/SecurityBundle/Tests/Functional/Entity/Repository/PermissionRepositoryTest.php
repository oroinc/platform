<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\SecurityBundle\Entity\Repository\PermissionRepository;

/**
 * @dbIsolation
 */
class PermissionRepositoryTest extends WebTestCase
{
    /**
     * @var PermissionRepository
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroSecurityBundle:Permission');

        $this->loadFixtures([
            'Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\LoadPermissionData'
        ]);
    }

    /**
     * @param array $inputData
     * @param string $expectedData
     *
     * @dataProvider addFindByIdsCriteriaProvider
     */
    public function testAddFindByIdsCriteria(array $inputData, $expectedData)
    {
        $queryBuilder = $this->repository->createQueryBuilder($inputData['alias']);

        $this->repository->addFindByIdsCriteria($queryBuilder, $inputData['ids']);

        $this->assertEquals($expectedData, $queryBuilder->getQuery()->getDQL());
    }

    /**
     * @param array $inputData
     * @param string $expectedData
     *
     * @dataProvider addFindByEntityClassCriteriaProvider
     */
    public function testAddFindByEntityClassCriteria(array $inputData, $expectedData)
    {
        $queryBuilder = $this->repository->createQueryBuilder($inputData['alias']);

        $this->repository->addFindByEntityClassCriteria($queryBuilder, $inputData['class']);

        $this->assertEquals($expectedData['dql'], $queryBuilder->getQuery()->getDQL());
        $this->assertEquals($expectedData['parameters'], $queryBuilder->getQuery()->getParameters());
    }

    /**
     * @return array
     */
    public function addFindByIdsCriteriaProvider()
    {
        return [
            [
                'input' => [
                    'alias' => 'ps',
                    'ids' => [],
                ],
                'expected' => 'SELECT ps FROM Oro\Bundle\SecurityBundle\Entity\Permission ps WHERE ps.id IN()',
            ],
            [
                'input' => [
                    'alias' => 'ps',
                    'ids' => [1, 2, 3],
                ],
                'expected' => 'SELECT ps FROM Oro\Bundle\SecurityBundle\Entity\Permission ps WHERE ps.id IN(1, 2, 3)',
            ],
        ];
    }

    /**
     * @return array
     */
    public function addFindByEntityClassCriteriaProvider()
    {
        return [
            [
                'input' => [
                    'alias' => 'ps',
                    'class' => 'Entity1',
                ],
                'expected' => [
                    'parameters' => new ArrayCollection([
                        new Parameter('class', 'Entity1'),
                    ]),
                    'dql' =>  'SELECT ps FROM Oro\Bundle\SecurityBundle\Entity\Permission ps ' .
                        'LEFT JOIN ps.applyToEntities ae WITH ae.name = :class ' .
                        'LEFT JOIN ps.excludeEntities ee WITH ee.name = :class ' .
                        'GROUP BY ps.id ' .
                        'HAVING ' .
                            '(ps.applyToAll = 1 AND COUNT(ee) = 0) ' .
                            'OR ' .
                            '(ps.applyToAll = 0 AND COUNT(ae) > 0)',
                ],
            ],
        ];
    }
}
