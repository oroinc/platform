<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;

/**
 * @dbIsolation
 */
class PostgresqlGridModifierTest extends WebTestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->container = $this->client->getKernel()->getContainer();
        $this->loadFixtures([
            'Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures\LoadUserData',
        ]);
    }

    public function testGridIsValidAndContainsEntityIdentifierInSorting()
    {
        if ($this->container->getParameter('database_driver') !== DatabaseDriverInterface::DRIVER_POSTGRESQL) {
            $this->markTestSkipped('Test runs only on PostgreSQL environment');
        }

        $getIdFunction = function ($array) {
            if (isset($array['id'])) {
                return $array['id'];
            }
            return null;
        };

        // any route just to initialize security context
        $this->client->request('GET', $this->getUrl('oro_user_index'));

        $isFoundIdentifier = false;
        $usersGrid = $this->container->get('oro_datagrid.datagrid.manager')->getDatagrid('users-grid');
        //this is just running my extension
        $usersGrid->getData();
        $queryBuilder = $usersGrid->getDatasource()->getQueryBuilder();
        $queryBuilder->setFirstResult(0)->setMaxResults(10);
        $idsWithLittleLimit = array_map($getIdFunction, $queryBuilder->getQuery()->getResult());
        $queryBuilder->setFirstResult(0)->setMaxResults(50000);
        $idsWithLargeLimit = array_map($getIdFunction, $queryBuilder->getQuery()->getResult());
        $orderByParts = $usersGrid->getDatasource()->getQueryBuilder()->getDQLPart('orderBy');

        foreach ($orderByParts as $part) {
            $parts = $part->getParts();
            if (in_array('u.id ASC', $parts, true) !== false) {
                $isFoundIdentifier = true;
                break;
            }
        }

        $this->assertTrue($isFoundIdentifier);
        $this->assertEquals($idsWithLargeLimit, $idsWithLittleLimit);
    }
}
