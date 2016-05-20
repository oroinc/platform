<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadItems;

/**
 * @dbIsolation
 */
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
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        // no DELETE and UPDATE operations
        $this->assertCount(0, $crawler->filter('[operation-name="UPDATE"]'));
        $this->assertCount(0, $crawler->filter('[operation-name="DELETE"]'));

        // default datagrid UPDATE and DELETE operations and DELETE mass action, it's index page for 'items-grid'
        $data = $this->assertDataGrid($crawler, 'items-grid');

        $this->assertCount(1, $data['data']);

        $this->assertArrayHasKey('update', $data['data'][0]['action_configuration']);
        $this->assertArrayHasKey('delete', $data['data'][0]['action_configuration']);
        $this->assertInternalType('array' , $data['data'][0]['action_configuration']['update']);
        $this->assertInternalType('array' , $data['data'][0]['action_configuration']['delete']);

        $this->assertArrayHasKey('delete', $data['metadata']['massActions']);
        $this->assertInternalType('array' , $data['metadata']['massActions']['delete']);
    }

    public function testViewPage()
    {
        /* @var $item Item */
        $item = $this->getReference(LoadItems::ITEM1);

        $crawler = $this->client->request('GET', $this->getUrl('oro_test_item_view', ['id' => $item->getId()]));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        // default DELETE and UPDATE operations
        $this->assertCount(1, $crawler->filter('[operation-name="UPDATE"]'));
        $this->assertCount(1, $crawler->filter('[operation-name="DELETE"]'));

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

        $this->assertEquals([], $data['metadata']['massActions']);
    }

    public function testUpdatePage()
    {
        /* @var $item Item */
        $item = $this->getReference(LoadItems::ITEM1);

        $crawler = $this->client->request('GET', $this->getUrl('oro_test_item_update', ['id' => $item->getId()]));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        // default DELETE operation, no UPDATE operation
        $this->assertCount(1, $crawler->filter('[operation-name="DELETE"]'));
        $this->assertCount(0, $crawler->filter('[operation-name="UPDATE"]'));
    }

    /**
     * @param Crawler $crawler
     * @param string $gridName
     * @return data
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
}
