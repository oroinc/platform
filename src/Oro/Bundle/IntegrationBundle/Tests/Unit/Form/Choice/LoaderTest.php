<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Choice;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\IntegrationBundle\Form\Choice\Loader;
use Oro\Component\TestUtils\ORM\OrmTestCase;

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
        $aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()->getMock();

        $em     = $this->getTestEntityManager();
        $loader = new Loader($aclHelper, $em, $allowedTypes);

        $ref = new \ReflectionProperty(
            'Oro\Bundle\SecurityBundle\Form\ChoiceList\AclProtectedQueryBuilderLoader',
            'queryBuilder'
        );
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
                'SELECT i FROM OroIntegrationBundle:Channel i WHERE 1 <> 1 ORDER BY i.name ASC'
            ],
            'test type is allowed'     => [
                ['test'],
                'SELECT i FROM OroIntegrationBundle:Channel i WHERE i.type IN(:allowedTypes) ORDER BY i.name ASC'
            ]
        ];
    }
}
