<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class GridViewControllerUnprivilegedTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([
            'Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures\LoadGridViewData',
        ]);
    }

    public function testPostActionShouldReturn403IfUserTriesToCreatePublicView()
    {
        $this->disableActionPermissions(['action:oro_datagrid_gridview_create_public']);

        $this->client->request('POST', $this->getUrl('oro_datagrid_api_rest_gridview_post'), [
            'label' => 'view',
            'type' => GridView::TYPE_PUBLIC,
            'grid_name' => 'testing-grid',
            'filters' => [],
            'sorters' => [],
        ]);
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    public function testPostActionShouldReturn403IfUserIsUnprivileged()
    {
        $this->disableEntityPermissions(['CREATE']);

        $this->client->request('POST', $this->getUrl('oro_datagrid_api_rest_gridview_post'), [
            'label' => 'view',
            'type' => GridView::TYPE_PRIVATE,
            'grid_name' => 'testing-grid',
            'filters' => [],
            'sorters' => [],
        ]);
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    public function testPutActionShouldReturn403IfUserTriesToShareView()
    {
        $this->disableActionPermissions(['action:oro_datagrid_gridview_create_public']);

        $gridView = $this->findPrivateView();

        $this->client->request('PUT', $this->getUrl('oro_datagrid_api_rest_gridview_put', ['id' => $gridView->getId()]), [
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
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    public function testPutActionShouldReturn403IfUserTriesToEditPublicView()
    {
        $this->disableActionPermissions(['action:oro_datagrid_gridview_edit_public']);

        $gridView = $this->findPublicView();

        $this->client->request('PUT', $this->getUrl('oro_datagrid_api_rest_gridview_put', ['id' => $gridView->getId()]), [
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
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    public function testPutActionShouldReturn403IfUserIsUprivileged()
    {
        $this->disableEntityPermissions(['EDIT']);

        $gridView = $this->findPrivateView();

        $this->client->request('PUT', $this->getUrl('oro_datagrid_api_rest_gridview_put', ['id' => $gridView->getId()]), [
            'label' => 'view2',
            'type' => GridView::TYPE_PRIVATE,
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
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    public function testDeleteActionShouldReturn403IfUserIsUnprivileged()
    {
        $this->disableEntityPermissions(['DELETE']);

        $id = $this->findLastGridView()->getId();

        $this->client->request('DELETE', $this->getUrl('oro_datagrid_api_rest_gridview_delete', ['id' => $id]));
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    private function disableActionPermissions(array $permissions)
    {
        $role = $this->getEntityManager()->getRepository('OroUserBundle:Role')->findOneByRole('ROLE_ADMINISTRATOR');

        $sid = $this->getAclManager()->getSid($role);
        $privileges = $this->getPrivilegeRepository()->getPrivileges($sid);
        $privilege = $privileges->filter(function(AclPrivilege $privilege) use ($permissions) {
            return in_array($privilege->getIdentity()->getId(), $permissions);
        })->first();

        foreach ($privilege->getPermissions() as $permission) {
            $permission->setAccessLevel(AccessLevel::NONE_LEVEL);
        }

        $this->getPrivilegeRepository()->savePrivileges($sid, $privileges);
    }

    private function disableEntityPermissions(array $permissions)
    {
        $role = $this->getEntityManager()->getRepository('OroUserBundle:Role')->findOneByRole('ROLE_ADMINISTRATOR');

        $sid = $this->getAclManager()->getSid($role);
        $privileges = $this->getPrivilegeRepository()->getPrivileges($sid);
        $privilege = $privileges->filter(function(AclPrivilege $privilege) {
            return $privilege->getIdentity()->getId() === 'entity:Oro\Bundle\DataGridBundle\Entity\GridView';
        })->first();

        foreach ($privilege->getPermissions() as $permission) {
            if (!in_array($permission->getName(), $permissions)) {
                continue;
            }
            $permission->setAccessLevel(AccessLevel::NONE_LEVEL);
        }

        $this->getPrivilegeRepository()->savePrivileges($sid, $privileges);
    }

    /**
     * @return AclPrivilegeRepository
     */
    private function getPrivilegeRepository()
    {
        return $this->getContainer()->get('oro_security.acl.privilege_repository');
    }

    /**
     * @return AclManager
     */
    private function getAclManager()
    {
        return $this->getContainer()->get('oro_security.acl.manager');
    }

    /**
     * @return GridView
     */
    private function findPublicView()
    {
        return $this->getGridViewRepository()->findOneBy([
            'type' => GridView::TYPE_PUBLIC,
        ], ['id' => 'ASC']);
    }

    /**
     * @return GridView
     */
    private function findPrivateView()
    {
        return $this->getGridViewRepository()->findOneBy([
            'type' => GridView::TYPE_PRIVATE,
        ], ['id' => 'ASC']);
    }

    /**
     * @return GridView
     */
    private function findLastGridView()
    {
        return $this->getGridViewRepository()->findOneBy([], ['id' => 'DESC']);
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
