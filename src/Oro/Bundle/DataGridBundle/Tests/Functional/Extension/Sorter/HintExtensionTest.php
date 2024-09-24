<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\DoctrineUtils\ORM\Walker\PostgreSqlOrderByNullsOutputResultModifier;

class HintExtensionTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([]);
    }

    public function testHintDisableOrderByModificationNullsIsAppliedByDefault()
    {
        /** @var ManagerInterface $dataGridManager */
        $dataGridManager = self::getContainer()->get('oro_datagrid.datagrid.manager');

        $dataGrid = $dataGridManager->getDatagrid('items-grid');

        self::assertArrayHasKey(
            PostgreSqlOrderByNullsOutputResultModifier::HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS,
            array_flip($dataGrid->getConfig()->getOrmQuery()->getHints())
        );
    }

    public function testHintDisableOrderByModificationNullsIsRemovedByGridConfiguration()
    {
        /** @var ManagerInterface $dataGridManager */
        $dataGridManager = self::getContainer()->get('oro_datagrid.datagrid.manager');

        $dataGrid = $dataGridManager->getDatagrid('items-values-grid');

        self::assertArrayNotHasKey(
            PostgreSqlOrderByNullsOutputResultModifier::HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS,
            array_flip($dataGrid->getConfig()->getOrmQuery()->getHints())
        );
    }
}
