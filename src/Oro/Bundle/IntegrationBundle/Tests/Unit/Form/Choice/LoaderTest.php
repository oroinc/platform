<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Choice;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\IntegrationBundle\Form\Choice\Loader;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;

class LoaderTest extends OrmTestCase
{
    /**
     * @dataProvider allowedTypesProvider
     *
     * @param array|null $allowedTypes
     * @param String     $expectedDQL
     */
    public function testQueryConfiguration($allowedTypes, $expectedDQL)
    {

        $em     = $this->getTestEntityManager();
        $loader = new Loader($em, $allowedTypes);

        $ref = new \ReflectionProperty('Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader', 'queryBuilder');
        $ref->setAccessible(true);

        /** @var QueryBuilder $qb */
        $qb = $ref->getValue($loader);
        $this->assertSame($expectedDQL, $qb->getDQL());
    }

    /**
     * @return array
     */
    public function allowedTypesProvider()
    {
        return [
            'types are not restricted' => [
                null,
                'SELECT i FROM OroIntegrationBundle:Channel i ORDER BY i.name ASC'
            ],
            'all types are denied'     => [
                [],
                'SELECT i FROM OroIntegrationBundle:Channel i WHERE 1 = 0 ORDER BY i.name ASC'
            ],
            'test type is allowed'     => [
                ['test'],
                'SELECT i FROM OroIntegrationBundle:Channel i WHERE i.type IN(\'test\') ORDER BY i.name ASC'
            ]
        ];
    }
}
