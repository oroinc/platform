<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadItems;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadItemsValues;
use Symfony\Component\DomCrawler\Crawler;

class ItemControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadItemsValues::class]);
    }

    public function testIndexPage()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_test_item_index'));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        // no DELETE and UPDATE operations
        $this->assertCount(0, $crawler->filter('a.action_button'));

        $this->assertCount(0, $crawler->filter('[operation-name="UPDATE"]'));
        $this->assertCount(0, $crawler->filter('[operation-name="DELETE"]'));

        // default datagrid UPDATE and DELETE operations and DELETE mass action, it's index page for 'items-grid'
        $data = $this->assertDatagrid($crawler, 'items-grid');

        $this->assertCount(3, $data['data']);

        $this->assertArrayHasKey('update', $data['data'][0]['action_configuration']);
        $this->assertArrayHasKey('delete', $data['data'][0]['action_configuration']);
        $this->assertIsArray($data['data'][0]['action_configuration']['update']);
        $this->assertIsArray($data['data'][0]['action_configuration']['delete']);

        // the "metadata" section is returned only if datagrid data is requested by AJAX,
        // during datagrid initialization the metadata is not returned together with data
        $this->assertArrayNotHasKey('metadata', $data);
    }

    public function testViewPage()
    {
        /* @var Item $item */
        $item = $this->getReference(LoadItems::ITEM1);

        $crawler = $this->client->request('GET', $this->getUrl('oro_test_item_view', ['id' => $item->getId()]));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        // default DELETE and UPDATE operations
        $this->assertPageContainsOperations($crawler, ['UPDATE', 'DELETE']);

        // no default datagrid operations and mass actions, it's no index page for 'items-values-grid'
        $data = $this->assertDatagrid($crawler, 'items-values-grid');

        $this->assertCount(1, $data['data']);

        $this->assertEquals(
            [
                'update' => false,
                'delete' => false,
            ],
            $data['data'][0]['action_configuration']
        );

        // the "metadata" section is returned only if datagrid data is requested by AJAX,
        // during datagrid initialization the metadata is not returned together with data
        $this->assertArrayNotHasKey('metadata', $data);
    }

    public function testUpdatePage()
    {
        /* @var Item $item */
        $item = $this->getReference(LoadItems::ITEM1);

        $crawler = $this->client->request('GET', $this->getUrl('oro_test_item_update', ['id' => $item->getId()]));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        // default DELETE operation, no UPDATE operation
        $this->assertPageContainsOperations($crawler, ['DELETE']);
    }

    public function testDatagrid()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_datagrid_index',
                [
                    'gridName' => 'items-grid',
                    'items-grid' => [
                        'originalRoute' => 'oro_test_item_index'
                    ]
                ]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(3, $data['data']);

        $this->assertArrayHasKey('update', $data['data'][0]['action_configuration']);
        $this->assertArrayHasKey('delete', $data['data'][0]['action_configuration']);
        $this->assertIsArray($data['data'][0]['action_configuration']['update']);
        $this->assertIsArray($data['data'][0]['action_configuration']['delete']);

        // the "metadata" section should be returned together with data
        // if datagrid data is requested by AJAX
        $this->assertArrayHasKey('metadata', $data);
        $this->assertArrayHasKey('massActions', $data['metadata']);
        $this->assertArrayHasKey('delete', $data['metadata']['massActions']);
        $this->assertIsArray($data['metadata']['massActions']['delete']);
    }

    private function assertDatagrid(Crawler $crawler, string $gridName): array
    {
        self::assertStringContainsString($gridName, $crawler->html());

        $container = $crawler->filter(sprintf('div[data-page-component-name="%s"]', $gridName));

        $this->assertCount(1, $container);

        $encodedOptions = $container->attr('data-page-component-options');

        $this->assertNotNull($encodedOptions);

        $options = json_decode($encodedOptions, true, 512, JSON_THROW_ON_ERROR);

        return $options['data'];
    }

    private function assertPageContainsOperations(Crawler $crawler, array $operations)
    {
        $node = $crawler->filter('a.operation-button');

        $this->assertCount(count($operations), $node);

        $router = $this->getContainer()->get('router');
        $container = $node->parents()->parents()->html();

        foreach ($operations as $operation) {
            self::assertStringContainsString(
                $router->generate('oro_action_operation_execute', ['operationName' => $operation]),
                $container
            );
        }
    }
}
