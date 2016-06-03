<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;

/**
 * @dbIsolation
 */
class PostgresqlGridModifierTest extends WebTestCase
{
    protected $gridName = 'users-grid';
    protected $gridParameters = [];
    protected $identifier = 'u.id';

    /**
     * @var ContainerInterface
     */
    protected $container;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
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
        $usersGrid = $this->container->get('oro_datagrid.datagrid.manager')->getDatagrid(
            $this->gridName,
            $this->gridParameters
        );
        //this is just running my extension
        $usersGrid->getData();
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $usersGrid->getDatasource()->getQueryBuilder();
        $queryBuilder->setFirstResult(0)->setMaxResults(10);
        $idsWithLittleLimit = array_map($getIdFunction, $queryBuilder->getQuery()->getResult());
        $queryBuilder->setFirstResult(0)->setMaxResults(50000);
        $idsWithLargeLimit = array_map($getIdFunction, $queryBuilder->getQuery()->getResult());
        $orderByParts = $usersGrid->getDatasource()->getQueryBuilder()->getDQLPart('orderBy');

        foreach ($orderByParts as $part) {
            $parts = $part->getParts();
            if (in_array($this->identifier . ' ASC', $parts, true) !== false) {
                $isFoundIdentifier = true;
                break;
            }
        }

        $this->assertTrue($isFoundIdentifier);
        $this->assertEquals($idsWithLargeLimit, $idsWithLittleLimit);

        // make sure query builder valid
        $queryBuilder->getQuery()->execute();
    }
}
