<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Reader\EntityReader;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;

class DatagridExportConnector extends EntityReader
{
    /**
     * @var ManagerInterface
     */
    protected $gridManager;

    /**
     * @param ManagerInterface $gridManager
     * @param ContextRegistry  $contextRegistry
     * @param ManagerRegistry  $registry
     */
    public function __construct(
        ManagerInterface $gridManager,
        ContextRegistry $contextRegistry,
        ManagerRegistry $registry
    ) {
        parent::__construct($contextRegistry, $registry);
        $this->gridManager = $gridManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        if ($context->hasOption('gridName')) {
            $grid = $this->gridManager->getDatagrid($context->getOption('gridName'));
            $datasource = $grid->getAcceptedDatasource();
            if ($datasource instanceof OrmDatasource) {
                $this->setSourceQuery($datasource->getResultQuery());
            } else {
                throw new \LogicException(
                    sprintf(
                        'Expected that "%s" grid uses ORM datasource, but actual datasource type is "%s"',
                        $context->getOption('gridName'),
                        get_class($datasource)
                    )
                );
            }
        } else {
            throw new InvalidConfigurationException(
                'Configuration of datagrid export connector must contain "gridName".'
            );
        }
    }
}
