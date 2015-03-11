<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;

/**
 * @dbIsolation
 * @dbReindex
 */
class GridViewControllerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->loadFixtures([
            'Oro\Bundle\TestFrameworkBundle\Fixtures\LoadUserData',
            'Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures\LoadGridViewData',
        ]);
    }

    public function testPostActionShouldReturn400IfSentDataAreInvalid()
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_datagrid_gridview'));
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 400);
    }

    public function testPostActionShouldReturn201IfSentDataAreValid()
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_datagrid_gridview'), [
            'name' => 'view',
            'type' => GridView::TYPE_PUBLIC,
            'grid_name' => 'testing-grid',
            'filters_data' => [],
            'sorters_data' => [],
        ]);
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 201);

        $response = json_decode($this->client->getResponse()->getContent(), true);
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

        $this->client->request('PUT', $this->getUrl('oro_api_put_datagrid_gridview', ['id' => $gridView->getId()]), [
            'name' => 'view2',
            'type' => GridView::TYPE_PUBLIC,
            'grid_name' => 'testing-grid2',
            'filters_data' => [
                'username' => [
                    'type' => 1,
                    'value' => 'adm',
                ],
            ],
            'sorters_data' => [
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

        $this->client->request('DELETE', $this->getUrl('oro_api_delete_datagrid_gridview', ['id' => $id]));
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 204);

        $this->assertNull($this->findGridView($id));
    }

    /**
     * @return GridView
     */
    private function findFirstGridView()
    {
        return $this->getGridViewRepository()->findOneBy([], ['id' => 'ASC']);
    }

    /**
     * @return GridView
     */
    private function findLastGridView()
    {
        return $this->getGridViewRepository()->findOneBy([], ['id' => 'DESC']);
    }

    /**
     * @return GridView
     */
    private function findGridView($id)
    {
        return $this->getGridViewRepository()->find($id);
    }

    /**
     * @return GridViewRepository
     */
    private function getGridViewRepository()
    {
        return $this->getEntityManager()->getRepository('OroDataGridBundle:GridView');
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }
}
