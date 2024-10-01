<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\ORM\Query\AST\Functions;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\ORM\Query\AST\Functions\AnyOf;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AnyOfTest extends WebTestCase
{
    private EntityManager $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(EntityFieldFallbackValue::class);
    }

    #[\Override]
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
        $configuration->addCustomStringFunction('anyof', AnyOf::class);

        $query = new Query($this->entityManager);
        $query->setDQL($dql);

        self::assertEquals($sql, $query->getSQL(), sprintf('Unexpected SQL for "%s"', $dql));
    }

    public function getDqlFunctionDataProvider(): array
    {
        return [
            'field' => [
                'dql' => 'SELECT eff.arrayValue FROM '
                    . EntityFieldFallbackValue::class . ' eff'
                    . ' WHERE \'bar\' = ANYOF(eff.arrayValue)'
                    . ' GROUP BY eff.id',
                'sql' => 'SELECT o0_.array_value AS array_value_0 FROM oro_entity_fallback_value o0_'
                    . ' WHERE \'bar\' = ANY(o0_.array_value) GROUP BY o0_.id',
            ],
            'function' => [
                'dql' => 'SELECT eff.arrayValue FROM '
                    . EntityFieldFallbackValue::class . ' eff'
                    . ' WHERE \'bar\' = ANYOF(STRING_TO_ARRAY(eff.scalarValue, :delimiter))'
                    . ' GROUP BY eff.id',
                'sql' => 'SELECT o0_.array_value AS array_value_0 FROM oro_entity_fallback_value o0_'
                    . ' WHERE \'bar\' = ANY(STRING_TO_ARRAY(o0_.scalar_value,?)) GROUP BY o0_.id',
            ],
        ];
    }
}
