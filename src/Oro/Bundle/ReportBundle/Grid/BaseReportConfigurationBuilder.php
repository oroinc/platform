<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\VirtualFieldProviderInterface;

class BaseReportConfigurationBuilder extends DatagridConfigurationBuilder
{
    /**
     * @var AbstractQueryDesigner
     */
    protected $source;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param string                        $gridName
     * @param AbstractQueryDesigner         $source
     * @param FunctionProviderInterface     $functionProvider
     * @param VirtualFieldProviderInterface $virtualFieldProvider
     * @param ManagerRegistry               $doctrine
     * @param ConfigManager                 $configManager
     */
    public function __construct(
        $gridName,
        AbstractQueryDesigner $source,
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        ManagerRegistry $doctrine,
        ConfigManager $configManager
    ) {
        parent::__construct($gridName, $source, $functionProvider, $virtualFieldProvider, $doctrine);

        $this->source = $source;
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();

        $className = $this->source->getEntity();
        $metadata = $this->configManager->getEntityMetadata($className);

        if (!$metadata || empty($metadata->routeView)) {
            return $configuration;
        }

        $fromPart = $configuration->offsetGetByPath('[source][query][from]');

        $entityAlias = null;
        $doctrineMetadata = $this->doctrine->getManagerForClass($className)
            ->getClassMetadata($className);
        $identifiers = $doctrineMetadata->getIdentifier();
        $primaryKey = array_shift($identifiers);

        foreach ($fromPart as $piece) {
            if ($piece['table'] == $className) {
                $entityAlias = $piece['alias'];
                break;
            }
        }

        if (!$entityAlias || !$primaryKey || count($identifiers) > 1 || !$this->isActionSupported($primaryKey)) {
            return $configuration;
        }

        $viewAction = array(
            'view' => array(
                'type'         => 'navigate',
                'label'        => 'View',
                'acl_resource' => 'VIEW;entity:' . $className,
                'icon'         => 'eye-open',
                'link'         => 'view_link',
                'rowAction'    => true
            )
        );

        $properties = array(
            $primaryKey => null,
            'view_link' => array(
                'type'   => 'url',
                'route'  => $metadata->routeView,
                'params' => array($primaryKey)
            )
        );

        $configuration->offsetAddToArrayByPath(
            '[source][query][select]',
            array("{$entityAlias}.{$primaryKey}")
        );
        $configuration->offsetAddToArrayByPath('[properties]', $properties);
        $configuration->offsetAddToArrayByPath('[actions]', $viewAction);

        return $configuration;
    }

    /**
     * @param string $primaryKey
     * @return bool
     */
    protected function isActionSupported($primaryKey)
    {
        $definition = json_decode($this->source->getDefinition(), true);
        $columnDefinition = $definition['columns'];

        $groupingColumns = !empty($definition['grouping_columns']) ? $definition['grouping_columns'] : array();

        if (count($groupingColumns) > 0) {
            foreach ($groupingColumns as $column) {
                if ($column['name'] == $primaryKey) {
                    return true;
                }
            }

            return false;
        } else {
            foreach ($columnDefinition as $column) {
                if (!empty($column['func'])
                    && !empty($column['func']['group_type'])
                    && $column['func']['group_type'] == 'aggregates') {
                    return false;
                }
            }

            return true;
        }
    }
}
