<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\ORM\Query\AST\Functions;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Oro\Bundle\EntityBundle\ORM\Query\AST\Functions\IsoYear;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class IsoYearTest extends WebTestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = self::getContainer()->get('doctrine')->getManagerForClass(User::class);
    }

    protected function tearDown(): void
    {
        unset($this->entityManager);
    }

    /**
     * @dataProvider getDqlFunctionDataProvider
     */
    public function testDqlFunction(string $dql, string $sql): void
    {
        $configuration = $this->entityManager->getConfiguration();
        $configuration->addCustomStringFunction('isoyear', IsoYear::class);

        $query = new Query($this->entityManager);
        $query->setDQL($dql);

        self::assertEquals($sql, $query->getSQL(), \sprintf('Unexpected SQL for "%s"', $dql));
    }

    public function getDqlFunctionDataProvider(): array
    {
        return [
            'simple' => [
                'dql' => 'SELECT ISOYEAR(u.createdAt) FROM ' . User::class . ' u GROUP BY u.id',
                'sql' => 'SELECT EXTRACT(ISOYEAR FROM o0_.createdAt) AS sclr_0 '
                    . 'FROM oro_user o0_ GROUP BY o0_.id',
            ],
            'dispatched' => [
                'dql' => 'SELECT ISOYEAR(TIMESTAMP(u.createdAt)) FROM '
                    . User::class . ' u GROUP BY u.id',
                'sql' => 'SELECT EXTRACT(ISOYEAR FROM "timestamp"(o0_.createdAt)) AS sclr_0 '
                    . 'FROM oro_user o0_ GROUP BY o0_.id',
            ],
        ];
    }
}
