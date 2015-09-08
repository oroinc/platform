<?php

namespace Oro\Bundle\DataGridBundle\Extension\Columns;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class ColumnsExtension extends AbstractExtension
{
    /**
     * Query param
     */
    const COLUMNS_PATH = 'columns';
    const ORDER_FIELD_NAME = 'order';
    const RENDER_FIELD_NAME = 'renderable';
    const DEFAULT_GRID_NAME = '__all__';
    const MINIFIED_COLUMNS_PARAM = 'c';

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

        /** Set default columns data from config */
        $this->addColumnsOrder($config, $data);

        /** Update columns data from url if exists */
        $columnsData = $this->updateColumnsDataFromUrl($config, $data);

        if (!$gridViews) {
            return;
        }

        $newGridView = $this->createNewGridView($config, $data);
        $currentState = $data->offsetGet('state');

        /** Get columns data from config or current view if no data in URL */
        if (!$columnsData) {
            /** Get default columns data from config */
            $columnsData = $config->offsetGet(self::COLUMNS_PATH);
            foreach ($gridViews as $gridView) {
                if ((int)$currentState['gridView'] === $gridView->getId()) {
                    /** Get columns state from current view */
                    $columnsData = $gridView->getColumnsData();
                }
            }
        }

        /** Save current columns state or restore to default view __all__ setting config columns data */
        $this->setState($data, $columnsData);
        /** Set config columns data */
        $this->setGridViewDefaultOrder($data, $newGridView->getColumnsData());
    }

    /**
     * Set columns params data from minified params
     *
     * @param ParameterBag $parameters
     */
    public function setParameters(ParameterBag $parameters)
    {
        if ($parameters->has(ParameterBag::MINIFIED_PARAMETERS)) {
            $minifiedParameters = $parameters->get(ParameterBag::MINIFIED_PARAMETERS);
            $columns = '';
            if (array_key_exists(self::MINIFIED_COLUMNS_PARAM, $minifiedParameters)) {
                $columns = $minifiedParameters[self::MINIFIED_COLUMNS_PARAM];
            }
            $parameters->set(self::MINIFIED_COLUMNS_PARAM, $columns);
        }

        parent::setParameters($parameters);
    }

    /**
     * Get Columns data from url
     *
     * @param DatagridConfiguration $config
     * @param MetadataObject $data
     *
     * @return array $columnsData
     */
    protected function updateColumnsDataFromUrl(DatagridConfiguration $config, MetadataObject $data)
    {
        $columnsData = [];

        if ($this->getParameters()->has(self::MINIFIED_COLUMNS_PARAM)) {
            /** Get columns data parameters from URL */
            $columnsParam = $this->getParameters()->get(self::MINIFIED_COLUMNS_PARAM, []);

            /** @var array $minifiedColumnsState */
            $minifiedColumnsState = $this->prepareColumnsParam($config, $columnsParam);

            $columns = $data->offsetGetOr(self::COLUMNS_PATH, []);
            foreach ($columns as $key => $column) {
                if (isset($column['name']) && isset($minifiedColumnsState[$column['name']])) {
                    $name = $column['name'];
                    $columnsData[$name] = $minifiedColumnsState[$name];
                    if (array_key_exists(self::ORDER_FIELD_NAME, $columnsData[$name])) {
                        $columns[$key][self::ORDER_FIELD_NAME] = $columnsData[$name][self::ORDER_FIELD_NAME];
                    }

                    if (array_key_exists(self::RENDER_FIELD_NAME, $columnsData[$name])) {
                        $columns[$key][self::RENDER_FIELD_NAME] = $columnsData[$name][self::RENDER_FIELD_NAME];
                    }
                }
            }

            $data->offsetSetByPath(self::COLUMNS_PATH, $columns);
        }

        return $columnsData;
    }

    /**
     * Get Columns State from ColumnsParam string
     *
     * @param DatagridConfiguration $config
     * @param string $columns like '51.11.21.30.40.61.71'
     *
     * @return array $columnsData
     */
    protected function prepareColumnsParam(DatagridConfiguration $config, $columns)
    {
        $columnsData = $config->offsetGet(self::COLUMNS_PATH);
        $columns = explode('.', $columns);

        $order = 0;
        foreach ($columnsData as $columnName => $columnData) {
            $options = str_split($columns[$order]);
            $columnsData[$columnName][self::ORDER_FIELD_NAME] = (int)$options[0];
            $columnsData[$columnName][self::RENDER_FIELD_NAME] = (int)$options[1];
            $order++;
        }

        return  $columnsData;
    }

    /**
     * * Adding column with order for state and for initialState
     *
     * @param DatagridConfiguration $config
     * @param MetadataObject        $data
     */
    protected function addColumnsOrder(DatagridConfiguration $config, MetadataObject $data)
    {
        $columnsData  = $config->offsetGet(self::COLUMNS_PATH);
        $columnsOrder = $this->buildColumnsOrder($columnsData);
        $columns      = $this->applyColumnsOrder($columnsData, $columnsOrder);

        $this->setInitialState($data, $columns);
        $this->setState($data, $columns);
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
        $newGridView  = new View(GridViewsExtension::DEFAULT_VIEW_ID);
        $columns      = $config->offsetGet(self::COLUMNS_PATH);
        $columnsOrder = $this->buildColumnsOrder($config->offsetGet(self::COLUMNS_PATH));
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
                if (GridViewsExtension::DEFAULT_VIEW_ID === $gridView['name']) {
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

                if (array_key_exists(self::RENDER_FIELD_NAME, $column) && true === $column[self::RENDER_FIELD_NAME]) {
                    $result[$name][self::RENDER_FIELD_NAME] = $column[self::RENDER_FIELD_NAME];
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
