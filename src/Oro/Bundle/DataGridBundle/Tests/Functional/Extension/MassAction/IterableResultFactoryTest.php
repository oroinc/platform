<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Extension\MassAction;

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
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityWithUserOwnership as TestEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class IterableResultFactoryTest extends WebTestCase
{
    use RolePermissionExtension;

    private const GRID_NAME = 'test-entity-grid';
    private const GRID_ONLY_NAME = 'test-entity-name-grid';

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader(LoadUserData::SIMPLE_USER, 'simple_password'));
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

    public function testCreateIterableResultWhenDataIdentifierNotInSelect(): void
    {
        $datagrid = $this->getDatagridManager()->getDatagrid(self::GRID_ONLY_NAME);

        $selectedItems = SelectedItems::createFromParameters([
            'values' => [],
            'inset' => true
        ]);

        $iterableResult = $this->getFactory()->createIterableResult(
            $datagrid->getAcceptedDatasource(),
            ActionConfiguration::create(['data_identifier' => 'item.id']),
            $datagrid->getConfig(),
            $selectedItems
        );

        $ids = array_map(
            fn (string $name) => $this->getReference($name)->getId(),
            [
                LoadTestEntitiesData::FIRST_SIMPLE_USER_ENTITY,
                LoadTestEntitiesData::SECOND_SIMPLE_USER_ENTITY,
                LoadTestEntitiesData::THIRD_SIMPLE_USER_ENTITY,
                LoadTestEntitiesData::FIRST_NOT_SIMPLE_USER_ENTITY,
            ]
        );

        $this->assertIterableResult($ids, 'id', $iterableResult);
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

    private function assertNames(array $expectedNames, IterableResultInterface $iterableResult): void
    {
        $this->assertIterableResult($expectedNames, 'name', $iterableResult);
    }

    private function assertIterableResult(
        array $expected,
        string $column,
        IterableResultInterface $iterableResult
    ): void {
        $values = array_map(
            static fn (ResultRecord $record) => $record->getValue($column),
            iterator_to_array($iterableResult)
        );

        self::assertEquals($expected, $values);
    }

    private function makeUserViewOnlyOwnEntities(): void
    {
        /** @var User $simpleUser */
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);

        $this->updateUserSecurityToken($simpleUser->getEmail());
        $this->updateRolePermission('ROLE_USER', TestEntity::class, AccessLevel::BASIC_LEVEL);
    }

    private function getFactory(): IterableResultFactory
    {
        return $this->client->getContainer()->get('oro_datagrid.extension.mass_action.iterable_result_factory.alias');
    }

    private function getDatagridManager(): Manager
    {
        return $this->client->getContainer()->get('oro_datagrid.datagrid.manager');
    }
}
