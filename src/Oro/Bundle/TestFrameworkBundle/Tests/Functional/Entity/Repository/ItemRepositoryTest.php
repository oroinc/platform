<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\Repository\ItemRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadItems;

/**
 * @dbIsolation
 */
class ItemRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadItems::class]);
    }

    /**
     * @return ItemRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(Item::class);
    }

    public function testGetItemsByIds()
    {
        /** @var Item $expectedItem1 */
        $expectedItem1 = $this->getReference(LoadItems::ITEM1);
        /** @var Item $expectedItem3 */
        $expectedItem3 = $this->getReference(LoadItems::ITEM3);

        $ids = [
            $expectedItem1->getId(),
            $expectedItem3->getId(),
        ];

        $this->assertEquals(
            [
                $expectedItem1,
                $expectedItem3,
            ],
            $this->getRepository()->getItemsByIds($ids)
        );
    }
}
