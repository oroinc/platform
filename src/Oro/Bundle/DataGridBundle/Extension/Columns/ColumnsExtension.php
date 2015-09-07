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
    const COLUMNS_PATH = 'columns';
    const ORDER_FIELD_NAME = 'order';
    const DEFAULT_GRID_NAME = '__all__';

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
        $columns = $config->offsetGetOr(self::COLUMNS_PATH, []);
        $this->processConfigs($config);

        return count($columns) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) {
            return;
        }

        $gridName  = $config->getName();
        $gridViews = $this->getGridViewRepository()->findGridViews($this->aclHelper, $currentUser, $gridName);

        $this->setInitialStateColumnsOrder($config, $data);

        if (!$gridViews) {
            return;
        }

        /** Get default columns data from config */
        $columnsData  = $config->offsetGet(self::COLUMNS_PATH);
        $newGridView  = $this->createNewGridView($config, $data);
        $currentState = $data->offsetGet('state');
        foreach ($gridViews as $gridView) {
            if ((int)$currentState['gridView'] === $gridView->getId()) {
                /** Get columns state from current view */
                $columnsData = $gridView->getColumnsData();
            }
        }

        /** Save current columns state or restore to default view __all__ setting config columns data */
        $this->setState($data, $columnsData);
        $this->setGridViewDefaultOrder($data, $newGridView->getColumnsData());
    }

    /**
     * @param DatagridConfiguration $config
     * @param MetadataObject        $data
     */
    protected function setInitialStateColumnsOrder(DatagridConfiguration $config, MetadataObject $data)
    {
        $columnsData  = $config->offsetGet(self::COLUMNS_PATH);
        $columnsOrder = $this->buildColumnsOrder($columnsData);
        $columns      = $this->applyColumnsOrder($columnsData, $columnsOrder);

        $this->setInitialState($data, $columns);
    }

    /**
     * Create grid view for default grid state __all__
     *
     * @param DatagridConfiguration $config
     * @param MetadataObject        $data
     *
     * @return View
     */
    protected function createNewGridView(DatagridConfiguration $config, MetadataObject $data)
    {
        $newGridView  = new View(self::DEFAULT_GRID_NAME);
        $columns      = $config->offsetGet(self::COLUMNS_PATH);
        $columnsOrder = $this->buildColumnsOrder($columns);
        $columns      = $this->applyColumnsOrder($columns, $columnsOrder);

        /** Set config columns state to __all__ grid view */
        $newGridView->setColumnsData($columns);
        $this->setState($data, $columns);
        $this->setInitialState($data, $columns);

        return $newGridView;
    }

    /**
     * @param MetadataObject $data
     * @param array          $columnsData
     */
    protected function setState(MetadataObject $data, array $columnsData)
    {
        $data->offsetAddToArray('state', [self::COLUMNS_PATH => $columnsData]);
    }

    /**
     * @param MetadataObject $data
     * @param array          $columnsData
     */
    protected function setInitialState(MetadataObject $data, array $columnsData)
    {
        $data->offsetAddToArray('initialState', [self::COLUMNS_PATH => $columnsData]);
    }

    /**
     * Set Default columns data for default grid view __all__
     *
     * @param MetadataObject $data
     * @param array          $columnsData
     */
    protected function setGridViewDefaultOrder(MetadataObject $data, $columnsData)
    {
        $gridViews = $data->offsetGet('gridViews');

        if ($gridViews && isset($gridViews['views'])) {
            foreach ($gridViews['views'] as &$gridView) {
                if (self::DEFAULT_GRID_NAME === $gridView['name']) {
                    $gridView[self::COLUMNS_PATH] = $columnsData;
                }
            }
            unset($gridView);
            $data->offsetSet('gridViews', $gridViews);
        }
    }

    /**
     * @param array $columns
     *
     * @return array
     */
    protected function buildColumnsOrder(array $columns = [])
    {
        $orders = [];

        foreach ($columns as $name => $column) {
            if (array_key_exists(self::ORDER_FIELD_NAME, $column)) {
                $orders[$name] = (int)$column[self::ORDER_FIELD_NAME];
            } else {
                $orders[$name] = 0;
            }
        }

        $iteration  = 1;
        $ignoreList = [];

        foreach ($orders as $name => &$order) {
            $iteration = $this->getFirstFreeOrder($iteration, $ignoreList);

            if (0 === $order) {
                $order = $iteration;
                $iteration++;
            } else {
                array_push($ignoreList, $order);
            }
        }

        unset($order);

        return $orders;
    }

    /**
     * @param array $columns
     * @param array $columnsOrder
     *
     * @return array
     */
    protected function applyColumnsOrder(array $columns, array $columnsOrder)
    {
        $result = [];
        foreach ($columns as $name => $column) {
            if (array_key_exists($name, $columnsOrder)) {
                $column[self::ORDER_FIELD_NAME]        = $columnsOrder[$name];
                $result[$name]                         = [];
                $result[$name][self::ORDER_FIELD_NAME] = $columnsOrder[$name];

                if (array_key_exists('renderable', $column) && true === $column['renderable']) {
                    $result[$name]['renderable'] = $column['renderable'];
                }
            }
        }

        return $result;
    }

    /**
     * Get first number which is not in ignore list
     *
     * @param int   $iteration
     * @param array $ignoreList
     *
     * @return int
     */
    protected function getFirstFreeOrder($iteration, array $ignoreList = [])
    {
        if (in_array($iteration, $ignoreList, true)) {
            ++$iteration;

            return $this->getFirstFreeOrder($iteration, $ignoreList);
        }

        return $iteration;
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
