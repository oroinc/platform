<?php

namespace Oro\Bundle\DataGridBundle\Extension\Columns;

use Symfony\Component\Security\Core\User\UserInterface;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\DataGridBundle\Tools\ColumnsHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\DataGridBundle\Entity\GridView;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ColumnsExtension extends AbstractExtension
{
    /**
     * Query param
     */
    const COLUMNS_PATH           = 'columns';
    const ORDER_FIELD_NAME       = 'order';
    const RENDER_FIELD_NAME      = 'renderable';
    const MINIFIED_COLUMNS_PARAM = 'c';
    const COLUMNS_PARAM          = '_columns';

    /** @var Registry */
    protected $registry;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var ColumnsHelper */
    protected $columnsHelper;

    /** @var GridView|null|bool */
    protected $defaultGridView = false;

    /**
     * @param Registry       $registry
     * @param SecurityFacade $securityFacade
     * @param AclHelper      $aclHelper
     * @param ColumnsHelper  $columnsHelper
     */
    public function __construct(
        Registry $registry,
        SecurityFacade $securityFacade,
        AclHelper $aclHelper,
        ColumnsHelper $columnsHelper
    ) {
        $this->registry       = $registry;
        $this->securityFacade = $securityFacade;
        $this->aclHelper      = $aclHelper;
        $this->columnsHelper  = $columnsHelper;
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
     * Should be applied before formatter extension
     *
     * {@inheritDoc}
     */
    public function getPriority()
    {
        return -10;
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

        /** Set default columns data to initial state from config */
        $this->setInitialStateColumnsOrder($config, $data);
        /** Set default columns data to metadata */
        $this->setColumnsOrder($config, $data);

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

        /** Get columns data from config */
        $columnsData = $this->getColumnsWithOrder($config);
        /** Get columns data from grid view */
        $gridViewColumnsData = null;
        if (isset($currentState['gridView'])) {
            foreach ($gridViews as $gridView) {
                if ((int)$currentState['gridView'] === $gridView->getId()) {
                    /** Get columns state from current view */
                    $gridViewColumnsData = $gridView->getColumnsData();
                    /** Get columns data from current view */
                    $columnsData = $gridViewColumnsData;
                    break;
                }
            }
        }

        /** Get columns data from config or current view if no data in URL */
        if (!empty($urlColumnsData)) {
            if ($this->columnsHelper->compareColumnsData($gridViewColumnsData, $urlColumnsData)) {
                $columnsData = $gridViewColumnsData;
            } else {
                $columnsData = $urlColumnsData;
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

        if ($this->getParameters() && $this->getParameters()->has(self::MINIFIED_COLUMNS_PARAM)) {
            /** Get columns data parameters from URL */
            $columnsParam = $this->getParameters()->get(self::MINIFIED_COLUMNS_PARAM, []);

            /** @var array $columnsConfigData */
            $columnsConfigData = $this->getColumnsWithOrder($config);
            /** @var array $minifiedColumnsState */
            $minifiedColumnsState = $this->columnsHelper->prepareColumnsParam($columnsConfigData, $columnsParam);

            $columns = $data->offsetGetOr(self::COLUMNS_PATH, []);
            foreach ($columns as $key => $column) {
                if (isset($column['name']) && isset($minifiedColumnsState[$column['name']])) {
                    $name = $column['name'];
                    $columnData = $minifiedColumnsState[$name];
                    if (array_key_exists(self::ORDER_FIELD_NAME, $columnData)) {
                        $columns[$key][self::ORDER_FIELD_NAME]      = $columnData[self::ORDER_FIELD_NAME];
                        $columnsData[$name][self::ORDER_FIELD_NAME] = $columnData[self::ORDER_FIELD_NAME];
                    }

                    if (array_key_exists(self::RENDER_FIELD_NAME, $columnData)) {
                        $columns[$key][self::RENDER_FIELD_NAME]      = $columnData[self::RENDER_FIELD_NAME];
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
     * Set default columns order to Metadata
     * Fill order for not configured fields
     *
     * @param DatagridConfiguration $config
     * @param MetadataObject        $data
     */
    protected function setColumnsOrder(DatagridConfiguration $config, MetadataObject $data)
    {
        $columnsOrdered = $this->getColumnsWithOrder($config);
        $columns  = $data->offsetGetOr(self::COLUMNS_PATH, []);
        foreach ($columns as $key => $column) {
            if (isset($column['name'])) {
                $columnName = $column['name'];
                if (isset($columnsOrdered[$columnName])) {
                    if (isset($columnsOrdered[$columnName]['order'])) {
                        $order = $columnsOrdered[$columnName]['order'];
                        $data->offsetSetByPath(sprintf('[columns][%s][order]', $key), $order);
                    }
                    if (isset($columnsOrdered[$columnName]['renderable'])) {
                        $renderable = $columnsOrdered[$columnName]['renderable'];
                        $data->offsetSetByPath(sprintf('[columns][%s][renderable]', $key), $renderable);
                    }
                }
            }
        }
    }

    /**
     * @param DatagridConfiguration $config
     * @param bool                  $default
     *
     * @return array
     */
    protected function getColumnsWithOrder(DatagridConfiguration $config, $default = false)
    {
        if (!$default) {
            $params          = $this->getParameters()->get(ParameterBag::ADDITIONAL_PARAMETERS, []);
            $defaultGridView = $this->getDefaultGridView($config->getName());
            if (isset($params['view']) && $defaultGridView && $params['view'] === $defaultGridView->getId()) {
                return $defaultGridView->getColumnsData();
            }
        }

        $columnsData  = $config->offsetGet(self::COLUMNS_PATH);
        $columnsOrder = $this->columnsHelper->buildColumnsOrder($columnsData);
        $columns      = $this->applyColumnsOrderAndRender($columnsData, $columnsOrder);

        return $columns;
    }

    /**
     * @param string $gridName
     *
     * @return GridView|null
     */
    protected function getDefaultGridView($gridName)
    {
        if ($this->defaultGridView === false) {
            if (!$currentUser = $this->getCurrentUser()) {
                return null;
            }
            
            $defaultGridView = $this->getGridViewRepository()->findDefaultGridView(
                $this->aclHelper,
                $currentUser,
                $gridName
            );

            $this->defaultGridView = $defaultGridView;
        }

        return $this->defaultGridView;
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
        $columns     = $this->getColumnsWithOrder($config, true);

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
     * @param MetadataObject $data
     * @param array          $columnsData
     */
    protected function setColumns(MetadataObject $data, array $columnsData)
    {
        $data->offsetAddToArray('columns', $columnsData);
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

                // Default value for render fields is true
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
     * @return UserInterface
     */
    protected function getCurrentUser()
    {
        $user = $this->securityFacade->getLoggedUser();
        if ($user instanceof UserInterface) {
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
