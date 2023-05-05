<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Choice;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\IntegrationBundle\Form\Choice\Loader;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class LoaderTest extends OrmTestCase
{
    /**
     * @dataProvider allowedTypesProvider
     */
    public function testQueryConfiguration(?array $allowedTypes, string $expectedDQL)
    {
        $aclHelper = $this->createMock(AclHelper::class);

        $em = $this->getTestEntityManager();
        $loader = new Loader($aclHelper, $em, $allowedTypes);

        /** @var QueryBuilder $qb */
        $qb = ReflectionUtil::getPropertyValue($loader, 'queryBuilder');
        $this->assertSame($expectedDQL, $qb->getDQL());
    }

    public function allowedTypesProvider(): array
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
