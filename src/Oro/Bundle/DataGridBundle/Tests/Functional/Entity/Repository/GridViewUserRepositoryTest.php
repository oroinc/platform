<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\DataGridBundle\Entity\GridViewUser;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewUserRepository;
use Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures\LoadGridViewData;
use Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures\LoadGridViewUserData;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class GridViewUserRepositoryTest extends AbstractDataGridRepositoryTest
{
    /** @var GridViewUserRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadGridViewUserData::class]);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository(GridViewUser::class);
    }

    public function testFindDefaultGridView()
    {
        $user = $this->getUser();
        $view = $this->repository->findDefaultGridView($this->aclHelper, $user, 'testing-grid');

        $this->assertNotNull($view);
        $this->assertGridViewExists($this->getReference(LoadGridViewUserData::GRID_VIEW_USER_3), [$view]);
    }

    public function testFindDefaultGridViews()
    {
        $user = $this->getUser();
        $views = $this->repository->findDefaultGridViews($this->aclHelper, $user, 'testing-grid');

        $this->assertCount(1, $views);
        $this->assertGridViewExists($this->getReference(LoadGridViewUserData::GRID_VIEW_USER_3), $views);
    }

    public function testFindByGridViewAndUser()
    {
        $gridView = $this->getReference(LoadGridViewData::GRID_VIEW_1);
        $user = $this->getUser();
        $view = $this->repository->findByGridViewAndUser($gridView, $user);

        $this->assertNotNull($view);
        $this->assertGridViewExists($this->getReference(LoadGridViewUserData::GRID_VIEW_USER_3), [$view]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUsername()
    {
        return LoadAdminUserData::DEFAULT_ADMIN_USERNAME;
    }
}
