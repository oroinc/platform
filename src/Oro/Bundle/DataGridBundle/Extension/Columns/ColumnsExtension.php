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
        $this->setInitialStateColumnsOrder($config, $data);
        if (!$gridViews) {
            $this->setStateColumnsOrder($config, $data);
        }

        /** Update columns data from url if exists */
        $urlColumnsData = $this->updateColumnsDataFromUrl($config, $data);

        if (!$gridViews) {
            return;
        }

        $newGridView = $this->createNewGridView($config, $data);
        $currentState = $data->offsetGet('state');

        /** Get columns data from grid view */
        $gridViewColumnsData = null;
        if (isset($currentState['gridView'])) {
            foreach ($gridViews as $gridView) {
                if ((int)$currentState['gridView'] === $gridView->getId()) {
                    /** Get columns state from current view */
                    $gridViewColumnsData = $gridView->getColumnsData();
                }
            }
        }

        /** Get columns data from config or current view if no data in URL */
        $columnsData = $this->getColumnsWithOrder($config);
        if (!empty($urlColumnsData)) {
            if ($this->compareColumnsData($gridViewColumnsData, $urlColumnsData)) {
                $columnsData = $gridViewColumnsData;
            } else {
                $columnsData = $urlColumnsData;
            }
        } elseif ($gridViewColumnsData !== null) {
            $columnsData = $gridViewColumnsData;
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

        if ($this->getParameters() && $this->getParameters()->has(self::MINIFIED_COLUMNS_PARAM)) {
            /** Get columns data parameters from URL */
            $columnsParam = $this->getParameters()->get(self::MINIFIED_COLUMNS_PARAM, []);

            /** @var array $minifiedColumnsState */
            $minifiedColumnsState = $this->prepareColumnsParam($config, $columnsParam);

            $columns = $data->offsetGetOr(self::COLUMNS_PATH, []);
            foreach ($columns as $key => $column) {
                if (isset($column['name'])) {
                    $name = $column['name'];
                } else {
                    $name = $key;
                }
                if ($name && isset($minifiedColumnsState[$name])) {
                    $columnData = $minifiedColumnsState[$name];
                    if (array_key_exists(self::ORDER_FIELD_NAME, $columnData)) {
                        $columns[$key][self::ORDER_FIELD_NAME] = $columnData[self::ORDER_FIELD_NAME];
                        $columnsData[$name][self::ORDER_FIELD_NAME] = $columnData[self::ORDER_FIELD_NAME];
                    }

                    if (array_key_exists(self::RENDER_FIELD_NAME, $columnData)) {
                        $columns[$key][self::RENDER_FIELD_NAME] = $columnData[self::RENDER_FIELD_NAME];
                        $columnsData[$name][self::RENDER_FIELD_NAME] = $columnData[self::RENDER_FIELD_NAME];
                    }
                }
            }
            $data->offsetSetByPath(self::COLUMNS_PATH, $columns);
            if (!empty($columnsData)) {
                $this->setState($data, $columnsData);
            }
        }

        return $columnsData;
    }

    /**
     * Check if data changed
     *
     * @param array $viewData
     * @param array $urlData
     *
     * @return bool
     */
    protected function compareColumnsData($viewData, $urlData)
    {
        if (!is_array($viewData) || !is_array($urlData) || empty($viewData) || empty($urlData)) {
            return false;
        }

        $diff = array_diff_key($viewData, $urlData);
        if (!empty($diff)) {
            return false;
        }
        $diff = array_diff_key($urlData, $viewData);
        if (!empty($diff)) {
            return false;
        }

        foreach ($viewData as $columnName => $columnData) {
            if (!isset($urlData[$columnName])) {
                return false;
            }
            $diff = array_diff_assoc($viewData[$columnName], $urlData[$columnName]);
            if (!empty($diff)) {
                return false;
            }
            $diff = array_diff_assoc($urlData[$columnName], $viewData[$columnName]);
            if (!empty($diff)) {
                return false;
            }
        }

        return true;
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

        //For non-minified saved grid views
        if (is_array($columns)) {
            foreach ($columns as $key => $value) {
                if (isset($value[self::ORDER_FIELD_NAME])) {
                    $columns[$key][self::ORDER_FIELD_NAME] = (int)$columns[$key][self::ORDER_FIELD_NAME];
                }
                if (isset($value[self::RENDER_FIELD_NAME])) {
                    $renderable = filter_var($value[self::RENDER_FIELD_NAME], FILTER_VALIDATE_BOOLEAN);
                    $columns[$key][self::RENDER_FIELD_NAME] = $renderable;
                }
            }
            return $columns;
        }

        //For minified column params
        $columns = explode('.', $columns);
        $index = 0;
        foreach ($columnsData as $columnName => $columnData) {
            $newColumnData = $this->getColumnData($index, $columns);
            if (!empty($newColumnData)) {
                $columnsData[$columnName][self::ORDER_FIELD_NAME] = $newColumnData['order'];
                $columnsData[$columnName][self::RENDER_FIELD_NAME] = $newColumnData['renderable'];
            }
            $index++;
        }

        return  $columnsData;
    }

    /**
     * Get new columns data
     *
     * @param int $index
     * @param array $columns
     * @return array
     */
    protected function getColumnData($index, $columns)
    {
        $result = array();

        if (!isset($columns[$index])) {
            return $result;
        }

        foreach ($columns as $key => $value) {
            $render = (bool)((int)(substr($value, -1)));
            $columnNumber = (int)(substr($value, 0, -1));
            if ($index === $columnNumber) {
                $result['order'] = $key;
                $result['renderable'] = $render;
                return $result;
            }
        }
        return $result;
    }

    /**
     * * Adding column with order for state and for initialState
     *
     * @param DatagridConfiguration $config
     * @param MetadataObject        $data
     */
    protected function setInitialStateColumnsOrder(DatagridConfiguration $config, MetadataObject $data)
    {
        $columns = $this->getColumnsWithOrder($config);
        $this->setInitialState($data, $columns);
    }

    /**
     * @param DatagridConfiguration $config
     * @param MetadataObject        $data
     */
    protected function setStateColumnsOrder(DatagridConfiguration $config, MetadataObject $data)
    {
        $columns = $this->getColumnsWithOrder($config);
        $this->setState($data, $columns);
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    protected function getColumnsWithOrder(DatagridConfiguration $config)
    {
        $columnsData  = $config->offsetGet(self::COLUMNS_PATH);
        $columnsOrder = $this->buildColumnsOrder($columnsData);
        $columns      = $this->applyColumnsOrderAndRender($columnsData, $columnsOrder);

        return $columns;
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
        $newGridView = new View(GridViewsExtension::DEFAULT_VIEW_ID);
        $columns     = $this->getColumnsWithOrder($config);

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
        $gridViews = $data->offsetGetOr('gridViews');

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

        $iteration  = 0;
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
    protected function applyColumnsOrderAndRender(array $columns, array $columnsOrder)
    {
        $result = [];
        foreach ($columns as $name => $column) {
            if (array_key_exists($name, $columnsOrder)) {
                $column[self::ORDER_FIELD_NAME]        = $columnsOrder[$name];
                $result[$name]                         = [];
                $result[$name][self::ORDER_FIELD_NAME] = $columnsOrder[$name];

                // Default value for render fiels is true
                $result[$name][self::RENDER_FIELD_NAME] = true;
                // If config value for render exist
                if (isset($column[self::RENDER_FIELD_NAME])) {
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
