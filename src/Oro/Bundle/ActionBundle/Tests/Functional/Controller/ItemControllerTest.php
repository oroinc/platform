<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadItems;
use Symfony\Component\DomCrawler\Crawler;

class ItemControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            'Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadItemsValues',
        ]);
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
        $data = $this->assertDataGrid($crawler, 'items-grid');

        $this->assertCount(3, $data['data']);

        $this->assertArrayHasKey('update', $data['data'][0]['action_configuration']);
        $this->assertArrayHasKey('delete', $data['data'][0]['action_configuration']);
        $this->assertInternalType('array', $data['data'][0]['action_configuration']['update']);
        $this->assertInternalType('array', $data['data'][0]['action_configuration']['delete']);

        // the "metadata" section is returned only if datagrid data is requested by AJAX,
        // during datagrid initialization the metadata is not returned together with data
        $this->assertArrayNotHasKey('metadata', $data);
    }

    public function testViewPage()
    {
        /* @var $item Item */
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
        /* @var $item Item */
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
        $this->assertInternalType('array', $data['data'][0]['action_configuration']['update']);
        $this->assertInternalType('array', $data['data'][0]['action_configuration']['delete']);

        // the "metadata" section should be returned together with data
        // if datagrid data is requested by AJAX
        $this->assertArrayHasKey('metadata', $data);
        $this->assertArrayHasKey('massActions', $data['metadata']);
        $this->assertArrayHasKey('delete', $data['metadata']['massActions']);
        $this->assertInternalType('array', $data['metadata']['massActions']['delete']);
    }

    /**
     * @param Crawler $crawler
     * @param string $gridName
     * @return array
     */
    protected function assertDataGrid(Crawler $crawler, $gridName)
    {
        $this->assertContains($gridName, $crawler->html());

        $container = $crawler->filter(sprintf('div[data-page-component-name="%s"]', $gridName));

        $this->assertCount(1, $container);

        $encodedOptions = $container->attr('data-page-component-options');

        $this->assertNotNull($encodedOptions);

        $options = json_decode($encodedOptions, true);

        return $options['data'];
    }

    /**
     * @param Crawler $crawler
     * @param array $operations
     */
    protected function assertPageContainsOperations(Crawler $crawler, array $operations)
    {
        $node = $crawler->filter('a.operation-button');

        $this->assertCount(count($operations), $node);

        $router = $this->getContainer()->get('router');
        $container = $node->parents()->parents()->html();

        foreach ($operations as $operation) {
            $this->assertContains(
                $router->generate('oro_action_operation_execute', ['operationName' => $operation]),
                $container
            );
        }
    }
}
