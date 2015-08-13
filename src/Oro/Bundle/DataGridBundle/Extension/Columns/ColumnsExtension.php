<?php

namespace Oro\Bundle\DataGridBundle\Extension\Columns;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class ColumnsExtension extends AbstractExtension
{
    /** @var Registry */
    protected $registry;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param Registry       $registry
     * @param SecurityFacade $securityFacade
     * @param AclHelper      $aclHelper
     */
    public function __construct(
        Registry $registry,
        SecurityFacade $securityFacade,
        AclHelper $aclHelper
    ) {
        $this->registry       = $registry;
        $this->securityFacade = $securityFacade;
        $this->aclHelper      = $aclHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $columns = $config->offsetGetOr(Configuration::COLUMNS_PATH, []);
        $this->processConfigs($config);

        return count($columns) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $this->validateConfiguration(
            new Configuration(),
            ['columns' => $config->offsetGetByPath(Configuration::COLUMNS_PATH)]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $newGridView = new View('__all__');
        $data->offsetAddToArray('initialState', ['columns' => $newGridView->getColumnsData()]);

        $currentUser = $this->getCurrentUser();

        if (!$currentUser) {
            return;
        }
        $gridName  = $config->getName();
        $gridViews = $this->getGridViewRepository()->findGridViews($this->aclHelper, $currentUser, $gridName);


        if (!$gridViews) {
            return;
        }

        $columns      = [];
        $currentState = $data->offsetGet('state');

        foreach ($gridViews as $gridView) {
            if ((int)$currentState['gridView'] === $gridView->getId()) {
                $columns = $gridView->getColumnsData();
            }
        }

        if (count($columns) > 0) {
            $data->offsetAddToArray('state', ['columns' => $columns]);
        } else {
            $data->offsetAddToArray('state', ['columns' => $newGridView->getColumnsData()]);
        }
    }

    /**
     * @return User
     */
    protected function getCurrentUser()
    {
        $user = $this->securityFacade->getLoggedUser();
        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    /**
     * @return GridViewRepository
     */
    protected function getGridViewRepository()
    {
        return $this->registry->getRepository('OroDataGridBundle:GridView');
    }
}
