<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\DoctrineUtils\ORM\SqlWalker;

class HintExtensionTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([]);
    }

    public function testHintDisableOrderByModificationNullsIsAppliedByDefault()
    {
        /** @var Manager $dataGridManager */
        $dataGridManager = static::getContainer()->get('oro_datagrid.datagrid.manager');

        $dataGrid = $dataGridManager->getDatagrid('items-grid');

        static::assertArrayHasKey(
            SqlWalker::HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS,
            array_flip($dataGrid->getConfig()->getOrmQuery()->getHints())
        );
    }

    public function testHintDisableOrderByModificationNullsIsRemovedByGridConfiguration()
    {
        /** @var Manager $dataGridManager */
        $dataGridManager = static::getContainer()->get('oro_datagrid.datagrid.manager');

        $dataGrid = $dataGridManager->getDatagrid('items-values-grid');

        static::assertArrayNotHasKey(
            SqlWalker::HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS,
            array_flip($dataGrid->getConfig()->getOrmQuery()->getHints())
        );
    }
}
