<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Extension\MassAction;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DTO\SelectedItems;
use Oro\Bundle\DataGridBundle\Extension\MassAction\IterableResultFactory;
use Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures\LoadTestEntitiesData;
use Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityWithUserOwnership as TestEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class IterableResultFactoryTest extends WebTestCase
{
    use RolePermissionExtension;

    const GRID_NAME = 'test-entity-grid';

    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader(LoadUserData::SIMPLE_USER, 'simple_password'));
        $this->loadFixtures([LoadTestEntitiesData::class]);
    }

    public function testCreateIterableResultWithoutIdentifierField()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Mass action must define identifier name');

        $datagrid = $this->getDatagridManager()->getDatagrid(self::GRID_NAME);
        $selectedItems = SelectedItems::createFromParameters([]);

        $this->getFactory()->createIterableResult(
            $datagrid->getAcceptedDatasource(),
            ActionConfiguration::create([]),
            $datagrid->getConfig(),
            $selectedItems
        );
    }

    public function testCreateIterableResultWhenAllItemsExceptSeveralSelected()
    {
        $datagrid = $this->getDatagridManager()->getDatagrid(self::GRID_NAME);

        /** @var TestEntity $secondTestEntity */
        $secondTestEntity = $this->getReference(LoadTestEntitiesData::SECOND_SIMPLE_USER_ENTITY);

        /** @var TestEntity $secondTestEntity */
        $thirdTestEntity = $this->getReference(LoadTestEntitiesData::THIRD_SIMPLE_USER_ENTITY);

        $selectedItems = SelectedItems::createFromParameters([
            'values' => [
                $secondTestEntity->getId(),
                $thirdTestEntity->getId()
            ],
            'inset' => false
        ]);

        $iterableResult = $this->getFactory()->createIterableResult(
            $datagrid->getAcceptedDatasource(),
            ActionConfiguration::create(['data_identifier' => 'item.id']),
            $datagrid->getConfig(),
            $selectedItems
        );

        $this->assertNames([
            LoadTestEntitiesData::FIRST_SIMPLE_USER_ENTITY,
            LoadTestEntitiesData::FIRST_NOT_SIMPLE_USER_ENTITY
        ], $iterableResult);
    }

    public function testCreateIterableResultWhenSeveralItemsSelected()
    {
        $datagrid = $this->getDatagridManager()->getDatagrid(self::GRID_NAME);

        /** @var TestEntity $secondTestEntity */
        $secondTestEntity = $this->getReference(LoadTestEntitiesData::SECOND_SIMPLE_USER_ENTITY);

        /** @var TestEntity $secondTestEntity */
        $thirdTestEntity = $this->getReference(LoadTestEntitiesData::THIRD_SIMPLE_USER_ENTITY);

        $selectedItems = SelectedItems::createFromParameters([
            'values' => [
                $secondTestEntity->getId(),
                $thirdTestEntity->getId()
            ],
            'inset' => true
        ]);

        $iterableResult = $this->getFactory()->createIterableResult(
            $datagrid->getAcceptedDatasource(),
            ActionConfiguration::create(['data_identifier' => 'item.id']),
            $datagrid->getConfig(),
            $selectedItems
        );

        $this->assertNames([
            LoadTestEntitiesData::SECOND_SIMPLE_USER_ENTITY,
            LoadTestEntitiesData::THIRD_SIMPLE_USER_ENTITY
        ], $iterableResult);
    }

    public function testCreateIterableResultWithObjectIdentifier()
    {
        /** @var TestEntity $secondTestEntity */
        $secondTestEntity = $this->getReference(LoadTestEntitiesData::SECOND_SIMPLE_USER_ENTITY);

        $datagrid = $this->getDatagridManager()->getDatagrid(self::GRID_NAME);
        $selectedItems = SelectedItems::createFromParameters([
            'values' => [$secondTestEntity->getId()],
            'inset' => true
        ]);

        $iterableResult = $this->getFactory()->createIterableResult(
            $datagrid->getAcceptedDatasource(),
            ActionConfiguration::create(['data_identifier' => 'item.id', 'object_identifier' => 'item']),
            $datagrid->getConfig(),
            $selectedItems
        );

        $this->assertNames([LoadTestEntitiesData::SECOND_SIMPLE_USER_ENTITY], $iterableResult);

        $items = iterator_to_array($iterableResult);
        $item = reset($items);

        $this->assertNotNull($item->getRootEntity());
    }

    public function testCreateIterableResultWithAcl()
    {
        $this->makeUserViewOnlyOwnEntities();

        $datagrid = $this->getDatagridManager()->getDatagrid(self::GRID_NAME);
        $selectedItems = SelectedItems::createFromParameters([]);

        $iterableResult = $this->getFactory()->createIterableResult(
            $datagrid->getAcceptedDatasource(),
            ActionConfiguration::create(['data_identifier' => 'item.id', 'object_identifier' => 'item']),
            $datagrid->getConfig(),
            $selectedItems
        );

        $this->assertNames([
            LoadTestEntitiesData::FIRST_SIMPLE_USER_ENTITY,
            LoadTestEntitiesData::SECOND_SIMPLE_USER_ENTITY,
            LoadTestEntitiesData::THIRD_SIMPLE_USER_ENTITY
        ], $iterableResult);
    }

    public function testCreateIterableResultWithoutAcl()
    {
        $this->makeUserViewOnlyOwnEntities();

        $datagrid = $this->getDatagridManager()->getDatagrid(self::GRID_NAME);
        $selectedItems = SelectedItems::createFromParameters([]);

        $gridConfig = $datagrid->getConfig();
        $gridConfig->offsetSetByPath(DatagridConfiguration::DATASOURCE_SKIP_ACL_APPLY_PATH, true);

        $iterableResult = $this->getFactory()->createIterableResult(
            $datagrid->getAcceptedDatasource(),
            ActionConfiguration::create(['data_identifier' => 'item.id', 'object_identifier' => 'item']),
            $gridConfig,
            $selectedItems
        );

        $this->assertNames([
            LoadTestEntitiesData::FIRST_SIMPLE_USER_ENTITY,
            LoadTestEntitiesData::SECOND_SIMPLE_USER_ENTITY,
            LoadTestEntitiesData::THIRD_SIMPLE_USER_ENTITY,
            LoadTestEntitiesData::FIRST_NOT_SIMPLE_USER_ENTITY
        ], $iterableResult);
    }

    /**
     * @param array $expectedNames
     * @param IterableResultInterface $iterableResult
     */
    private function assertNames(array $expectedNames, IterableResultInterface $iterableResult)
    {
        $names = array_map(function (ResultRecord $record) {
            return $record->getValue('name');
        }, iterator_to_array($iterableResult));

        static::assertEquals($expectedNames, $names);
    }

    private function makeUserViewOnlyOwnEntities()
    {
        /** @var ObjectManager $entityManager */
        $entityManager = $this->client->getContainer()->get('doctrine')->getManagerForClass('OroUserBundle:User');

        /** @var User $user */
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);
        $organization = $entityManager->getRepository('OroOrganizationBundle:Organization')
            ->find(self::AUTH_ORGANIZATION);

        $token = new UsernamePasswordOrganizationToken($simpleUser, $simpleUser->getUsername(), 'main', $organization);
        $this->client->getContainer()->get('security.token_storage')->setToken($token);

        $this->updateRolePermission('ROLE_USER', TestEntity::class, AccessLevel::BASIC_LEVEL);
    }

    /**
     * @return IterableResultFactory
     */
    private function getFactory()
    {
        return $this->client->getContainer()->get('oro_datagrid.extension.mass_action.iterable_result_factory.alias');
    }

    /**
     * @return Manager
     */
    private function getDatagridManager()
    {
        return $this->client->getContainer()->get('oro_datagrid.datagrid.manager');
    }
}
