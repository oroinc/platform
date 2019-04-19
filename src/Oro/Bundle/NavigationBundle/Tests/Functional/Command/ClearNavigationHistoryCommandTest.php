<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Command;

use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\NavigationBundle\Entity\Repository\HistoryItemRepository;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\NavigationHistoryItemData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ClearNavigationHistoryCommandTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient();
        $this->loadFixtures([NavigationHistoryItemData::class]);
    }

    public function testExecuteWithNonValidInterval()
    {
        $this->assertContains(
            "Value 'invalid' should be valid date interval",
            $this->runCommand('oro:navigation:history:clear', ['--interval' => 'invalid'])
        );
    }

    public function testExecuteWithValidInterval()
    {
        /** @var HistoryItemRepository $repo */
        $repo = $this->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(NavigationHistoryItem::class);

        $this->assertCount(5, $repo->findAll());

        $this->assertContains(
            "'2' items deleted from navigation history.",
            $this->runCommand('oro:navigation:history:clear', ['--interval' => '3 days'])
        );

        /** @var NavigationHistoryItem[] $items */
        $items = $repo->findAll();

        $this->assertCount(3, $items);

        foreach ($items as $item) {
            $this->assertTrue(in_array($item->getTitle(), [
                NavigationHistoryItemData::NAVIGATION_HISTORY_ITEM_3,
                NavigationHistoryItemData::NAVIGATION_HISTORY_ITEM_4,
                NavigationHistoryItemData::NAVIGATION_HISTORY_ITEM_5,
            ]));
        }
    }
}
