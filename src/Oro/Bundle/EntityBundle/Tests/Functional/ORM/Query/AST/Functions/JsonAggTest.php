<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\ORM\Query\AST\Functions;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Oro\Bundle\EntityBundle\ORM\Query\AST\Functions\JsonAgg;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\Entity\TestDecimalEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class JsonAggTest extends WebTestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = self::getContainer()->get('doctrine')->getManagerForClass(TestDecimalEntity::class);
    }

    /**
     * @dataProvider getDqlFunctionDataProvider
     */
    public function testDqlFunction(string $dql, string $sql): void
    {
        $configuration = $this->entityManager->getConfiguration();
        $configuration->addCustomStringFunction('json_agg', JsonAgg::class);

        $query = new Query($this->entityManager);
        $query->setDQL($dql);

        self::assertEquals($sql, $query->getSQL(), \sprintf('Unexpected SQL for "%s"', $dql));
    }

    public function getDqlFunctionDataProvider(): array
    {
        return [
            'simple' => [
                'dql' => 'SELECT JSON_AGG(d.decimalProperty) FROM ' . TestDecimalEntity::class . ' d GROUP BY d.id',
                'sql' => 'SELECT json_agg(o0_.decimal_property) AS sclr_0 '
                    . 'FROM oro_test_decimal_entity o0_ GROUP BY o0_.id',
            ],
            'dispatched' => [
                'dql' => 'SELECT JSON_AGG(CAST(d.decimalProperty AS string)) FROM '
                    . TestDecimalEntity::class . ' d GROUP BY d.id',
                'sql' => 'SELECT json_agg(CAST(o0_.decimal_property AS varchar)) AS sclr_0 '
                    . 'FROM oro_test_decimal_entity o0_ GROUP BY o0_.id',
            ],
            'dispatched, order by' => [
                'dql' => 'SELECT JSON_AGG(CAST(d.decimalProperty AS string) ORDER BY d.id) FROM '
                    . TestDecimalEntity::class . ' d GROUP BY d.id',
                'sql' => 'SELECT json_agg(CAST(o0_.decimal_property AS varchar) ORDER BY o0_.id ASC) '
                    . 'AS sclr_0 FROM oro_test_decimal_entity o0_ GROUP BY o0_.id',
            ],
        ];
    }
}
