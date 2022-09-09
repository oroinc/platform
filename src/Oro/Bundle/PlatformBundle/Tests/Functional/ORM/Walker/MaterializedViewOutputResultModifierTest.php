<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional\ORM\Walker;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewManager;
use Oro\Bundle\PlatformBundle\Tests\Functional\MaterializedView\MaterializedViewsAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;
use Oro\Component\DoctrineUtils\ORM\Walker\MaterializedViewOutputResultModifier;

class MaterializedViewOutputResultModifierTest extends WebTestCase
{
    use MaterializedViewsAwareTestTrait;

    private MaterializedViewManager $materializedViewManager;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->materializedViewManager = self::getContainer()->get('oro_platform.materialized_view.manager');
    }

    public static function tearDownAfterClass(): void
    {
        self::deleteAllMaterializedViews(self::getContainer());

        parent::tearDownAfterClass();
    }

    /**
     * @dataProvider queryDataProvider
     */
    public function testQuery(string $DQL, ArrayCollection $parameters, string $expectedSQL): void
    {
        /** @var EntityManager $entityManager */
        $entityClass = User::class;
        $entityManager = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass($entityClass);

        $query = $entityManager
            ->createQuery(sprintf($DQL, $entityClass))
            ->setParameters($parameters);

        $materializedViewName = self::generateMaterializedViewRandomName();
        $materializedViewEntity = $this->materializedViewManager->createByQuery($query, $materializedViewName);
        self::assertEquals($materializedViewName, $materializedViewEntity->getName());

        $queryWithHint = QueryUtil::cloneQuery($query);
        $queryWithHint->setHint(MaterializedViewOutputResultModifier::USE_MATERIALIZED_VIEW, $materializedViewName);

        self::assertEquals($query->getResult(), $queryWithHint->getResult());
        self::assertNotEquals($query->getSQL(), $queryWithHint->getSQL());
        self::assertEquals(sprintf($expectedSQL, $materializedViewName), $queryWithHint->getSQL());
    }

    public function queryDataProvider(): array
    {
        return [
            [
                'DQL' => 'SELECT u FROM %s u',
                'parameters' => new ArrayCollection(),
                'expectedSQL' => 'SELECT * FROM "%s" o0_',
            ],
            [
                'DQL' => 'SELECT u FROM %s u WHERE u.createdAt > :createdAt',
                'parameters' => new ArrayCollection(
                    [
                        new Parameter(
                            'createdAt',
                            new \DateTime('today -1 day', new \DateTimeZone('UTC')),
                            Types::DATETIME_MUTABLE
                        ),
                    ]
                ),
                'expectedSQL' => 'SELECT * FROM "%s" o0_',
            ],
        ];
    }
}
