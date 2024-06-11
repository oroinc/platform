<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\ORM\Query\AST\Functions;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\ORM\Query\AST\Functions\StringToArray;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class StringToArrayTest extends WebTestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(EntityFieldFallbackValue::class);
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
        $configuration->addCustomStringFunction('string_to_array', StringToArray::class);

        $query = new Query($this->entityManager);
        $query->setDQL($dql);

        self::assertEquals($sql, $query->getSQL(), sprintf('Unexpected SQL for "%s"', $dql));
    }

    public function getDqlFunctionDataProvider(): array
    {
        return [
            'string' => [
                'dql' => 'SELECT STRING_TO_ARRAY(\'xx,yy,zz\', :delimiter) FROM '
                    . EntityFieldFallbackValue::class . ' eff GROUP BY eff.id',
                'sql' => 'SELECT STRING_TO_ARRAY(\'xx,yy,zz\',?) AS sclr_0'
                    . ' FROM oro_entity_fallback_value o0_ GROUP BY o0_.id',
            ],
            'field' => [
                'dql' => 'SELECT STRING_TO_ARRAY(eff.scalarValue, :delimiter) FROM '
                    . EntityFieldFallbackValue::class . ' eff GROUP BY eff.id',
                'sql' => 'SELECT STRING_TO_ARRAY(o0_.scalar_value,?) AS sclr_0'
                    . ' FROM oro_entity_fallback_value o0_ GROUP BY o0_.id',
            ],
        ];
    }
}
