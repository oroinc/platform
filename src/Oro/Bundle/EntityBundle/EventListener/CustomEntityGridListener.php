<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

class CustomEntityGridListener
{
    /** @var Router */
    protected $router;

    /** @var DatagridInterface[] */
    protected $visitedDatagrids = array();

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $datagrid = $event->getDatagrid();
        $config   = $event->getConfig();

        // remember the current datagrid to further usage in getLinkProperty method
        $this->addVisitedDatagrid($datagrid);

        // enable DynamicFieldsExtension to add custom fields
        $config->offsetSet('extended_entity_name', $datagrid->getParameters()->get('class_name'));
    }

    /**
     * @param string $gridName
     * @param string $keyName
     * @param array  $node
     *
     * @return callable
     */
    public function getLinkProperty($gridName, $keyName, $node)
    {
        if (!isset($node['route'])) {
            return false;
        }

        $router = $this->router;
        $route  = $node['route'];

        return function (ResultRecord $record) use ($gridName, $router, $route) {
            $datagrid  = $this->getVisitedDatagrid($gridName);
            $className = $datagrid->getParameters()->get('class_name');
            return $router->generate(
                $route,
                [
                    'entityName' => str_replace('\\', '_', $className),
                    'id' => $record->getValue('id')
                ]
            );
        };
    }

    /**
     * @param DatagridInterface $datagrid
     */
    protected function addVisitedDatagrid(DatagridInterface $datagrid)
    {
        $this->visitedDatagrids[$datagrid->getName()] = $datagrid;
    }

    /**
     * @param string $gridName
     *
     * @return DatagridInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function getVisitedDatagrid($gridName)
    {
        if (!isset($this->visitedDatagrids[$gridName])) {
            throw new \InvalidArgumentException(
                sprintf('Can\'t get instance of grid "%s".', $gridName)
            );
        }

        return $this->visitedDatagrids[$gridName];
    }
}
