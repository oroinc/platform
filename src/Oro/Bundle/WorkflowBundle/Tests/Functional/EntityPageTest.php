<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityPageTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    public function testEntityWorkflowsAction(): void
    {
        $item = new Item();
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Item::class);
        $entityManager->persist($item);
        $entityManager->flush();

        $crawler = $this->client->request('GET', $this->getUrl('oro_test_item_view', ['id' => $item->getId()]));

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertNotEmpty($crawler->html());

        self::assertStringContainsString('Oro Test Workflow', $crawler->html());
        self::assertStringContainsString('To Second Step', $crawler->html());
    }
}
