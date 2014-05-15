<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\VirtualFieldProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class ReportDatagridConfigurationProvider implements ConfigurationProviderInterface
{
    const GRID_PREFIX = 'oro_report_table_';

    /**
     * @var FunctionProviderInterface
     */
    protected $functionProvider;

    /**
     * @var VirtualFieldProviderInterface
     */
    protected $virtualFieldProvider;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var DatagridConfiguration
     */
    private $configuration = null;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * Constructor
     *
     * @param FunctionProviderInterface     $functionProvider
     * @param VirtualFieldProviderInterface $virtualFieldProvider ,
     * @param ManagerRegistry               $doctrine
     * @param ConfigManager $configManager
     */
    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        ManagerRegistry $doctrine,
        ConfigManager $configManager
    ) {
        $this->functionProvider     = $functionProvider;
        $this->virtualFieldProvider = $virtualFieldProvider;
        $this->doctrine             = $doctrine;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($gridName)
    {
        return (strpos($gridName, self::GRID_PREFIX) === 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration($gridName)
    {
        if ($this->configuration === null) {
            $id      = intval(substr($gridName, strlen(self::GRID_PREFIX)));
            $repo    = $this->doctrine->getRepository('OroReportBundle:Report');
            $report  = $repo->find($id);
            $builder = new ReportDatagridConfigurationBuilder(
                $gridName,
                $report,
                $this->functionProvider,
                $this->virtualFieldProvider,
                $this->doctrine
            );

            $this->configuration = $builder->getConfiguration();

            $this->addViewActionToParams($report->getEntity());
        }

        return $this->configuration;
    }

    protected function addViewActionToParams($className)
    {
        $metadata = $this->configManager->getEntityMetadata($className);

        if (!$metadata || empty($metadata->routeView)) {
            return;
        }

        $fromPart = $this->configuration->offsetGetByPath('[source][query][from]');

        $entityAlias = null;
        $doctrineMetadata = $this->doctrine->getManagerForClass($className)->getClassMetadata($className);
        $identifiers = $doctrineMetadata->getIdentifier();
        $pkName = array_shift($identifiers);

        foreach ($fromPart as $piece) {
            if ($piece['table'] == $className) {
                $entityAlias = $piece['alias'];
            }
        }

        if (!$entityAlias || $pkName === null || count($identifiers) > 1) {
            return;
        }

        $viewAction = array(
            'view' => array(
                'type'         => 'navigate',
                'label'        => 'View',
                'icon'         => 'eye-open',
                'link'         => 'view_link',
                'rowAction'    => true
            )
        );

        $idName = uniqid('id_');

        $properties = array(
            $idName        => array(),
            'view_link' => array(
                'type'   => 'url',
                'route'  => $metadata->routeView,
                'params' => array('id' => $idName)
            )
        );

        $this->configuration->offsetAddToArrayByPath(
            '[source][query][select]',
            array("{$entityAlias}.{$pkName} as {$idName}")
        );
        $this->configuration->offsetAddToArrayByPath('[properties]', $properties);
        $this->configuration->offsetAddToArrayByPath('[actions]', $viewAction);
    }

    /**
     * Check whether a report is valid or not
     *
     * @param string $gridName
     * @return bool
     */
    public function isReportValid($gridName)
    {
        try {
            $this->getConfiguration($gridName);
        } catch (InvalidConfigurationException $invalidConfigEx) {
            return false;
        }

        return true;
    }
}
