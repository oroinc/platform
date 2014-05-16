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
     * @var string
     */
    protected $className;

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

        $this->className = $source->getEntity();
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();

        $metadata = $this->configManager->getEntityMetadata($this->className);

        if (!$metadata || empty($metadata->routeView)) {
            return $configuration;
        }

        $fromPart = $configuration->offsetGetByPath('[source][query][from]');

        $entityAlias = null;
        $doctrineMetadata = $this->doctrine->getManagerForClass($this->className)
            ->getClassMetadata($this->className);
        $identifiers = $doctrineMetadata->getIdentifier();
        $primaryKey = array_shift($identifiers);

        foreach ($fromPart as $piece) {
            if ($piece['table'] == $this->className) {
                $entityAlias = $piece['alias'];
                break;
            }
        }

        if (!$entityAlias || $primaryKey === null || count($identifiers) > 1) {
            return $configuration;
        }

        $viewAction = array(
            'view' => array(
                'type'         => 'navigate',
                'label'        => 'View',
                'acl_resource' => 'VIEW;entity:' . $this->className,
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
}
