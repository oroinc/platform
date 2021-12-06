<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures\LoadGridViewData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class GridViewControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([LoadGridViewData::class]);
    }

    public function testPostActionShouldReturn400IfSentDataAreInvalid()
    {
        $this->client->jsonRequest('POST', $this->getUrl('oro_datagrid_api_rest_gridview_post'));
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 400);
    }

    public function testPostActionShouldReturn201IfSentDataAreValid()
    {
        $this->client->jsonRequest('POST', $this->getUrl('oro_datagrid_api_rest_gridview_post'), [
            'label' => 'view',
            'type' => GridView::TYPE_PUBLIC,
            'grid_name' => 'testing-grid',
            'filters' => [],
            'sorters' => [],
        ]);
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 201);

        $response = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('id', $response);
        $createdGridView = $this->findGridView($response['id']);
        $this->assertEquals('admin', $createdGridView->getOwner()->getUsername());
        $this->assertEquals('view', $createdGridView->getName());
        $this->assertEquals(GridView::TYPE_PUBLIC, $createdGridView->getType());
        $this->assertEquals('testing-grid', $createdGridView->getGridName());
        $this->assertEmpty($createdGridView->getFiltersData());
        $this->assertEmpty($createdGridView->getSortersData());
    }

    public function testPutActionShouldReturn204IfSentDataAreValid()
    {
        $gridView = $this->findFirstGridView();

        $url = $this->getUrl('oro_datagrid_api_rest_gridview_put', ['id' => $gridView->getId()]);
        $this->client->jsonRequest('PUT', $url, [
            'label' => 'view2',
            'type' => GridView::TYPE_PUBLIC,
            'grid_name' => 'testing-grid2',
            'filters' => [
                'username' => [
                    'type' => 1,
                    'value' => 'adm',
                ],
            ],
            'sorters' => [
                'username' => 1,
            ],
        ]);
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 204);

        $updatedGridView = $this->findGridView($gridView->getId());
        $this->assertEquals('simple_user', $updatedGridView->getOwner()->getUsername());
        $this->assertEquals('view2', $updatedGridView->getName());
        $this->assertEquals(GridView::TYPE_PUBLIC, $updatedGridView->getType());
        $this->assertEquals('testing-grid2', $updatedGridView->getGridName());
        $this->assertEquals([
            'username' => [
                'type' => 1,
                'value' => 'adm',
            ],
        ], $updatedGridView->getFiltersData());
        $this->assertEquals([
            'username' => 1,
        ], $updatedGridView->getSortersData());
    }

    public function testDeleteActionShouldReturn204()
    {
        $id = $this->findLastGridView()->getId();

        $this->client->jsonRequest('DELETE', $this->getUrl('oro_datagrid_api_rest_gridview_delete', ['id' => $id]));
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 204);

        $this->assertNull($this->findGridView($id));
    }

    private function findFirstGridView(): GridView
    {
        return $this->getGridViewRepository()->findOneBy([], ['id' => 'ASC']);
    }

    private function findLastGridView(): GridView
    {
        return $this->getGridViewRepository()->findOneBy([], ['id' => 'DESC']);
    }

    private function findGridView(int $id): ?GridView
    {
        return $this->getGridViewRepository()->find($id);
    }

    private function getGridViewRepository(): GridViewRepository
    {
        return $this->getEntityManager()->getRepository(GridView::class);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }
}
