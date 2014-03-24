<?php

namespace Oro\Bundle\SegmentBundle\Grid;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Model\DatagridSourceSegmentProxy;
use Oro\Bundle\DataGridBundle\Extension\Export\ExportExtension;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class SegmentDatagridConfigurationBuilder extends DatagridConfigurationBuilder
{
    /** @var string */
    protected $route;

    /** @var string */
    protected $identifierName;

    /** @var string */
    protected $entityName;

    /**
     * Constructor
     *
     * @param string                    $gridName
     * @param Segment                   $segment
     * @param FunctionProviderInterface $functionProvider
     * @param ManagerRegistry           $doctrine
     * @param ConfigManager             $configManager
     */
    public function __construct(
        $gridName,
        Segment $segment,
        FunctionProviderInterface $functionProvider,
        ManagerRegistry $doctrine,
        ConfigManager $configManager
    ) {
        $em = $doctrine->getManagerForClass($segment->getEntity());
        parent::__construct(
            $gridName,
            new DatagridSourceSegmentProxy($segment, $em),
            $functionProvider,
            $doctrine
        );

        $this->entityName     = $segment->getEntity();
        $entityMetadata = $configManager->getEntityMetadata($this->entityName);
        if ($entityMetadata && $entityMetadata->routeView) {
            $this->route = $entityMetadata->routeView;
        }

        $classMetadata        = $em->getClassMetadata($this->entityName);
        $identifiers          = $classMetadata->getIdentifier();
        $this->identifierName = array_shift($identifiers);

        $this->config->offsetSetByPath('[source][acl_resource]', 'oro_segment_view');
        $this->config->offsetSetByPath(ExportExtension::EXPORT_OPTION_PATH, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $config = parent::getConfiguration();

        $config->offsetAddToArray(
            'properties',
            [
                $this->identifierName => null,
                'view_link'  => [
                    'type'   => 'url',
                    'route'  => $this->route,
                    'params' => ['id']
                ]
            ]
        );

        $config->offsetAddToArray(
            'actions',
            [
                'view' => [
                    'type'         => 'navigate',
                    'acl_resource' => 'VIEW;entity:'.$this->entityName,
                    'label'        => 'View',
                    'icon'         => 'user',
                    'link'         => 'view_link',
                    'rowAction'    => true,
                ]
            ]
        );

        $tableAlias = $config->offsetGetByPath('[source][query_config][table_aliases]');
        $tableAlias = array_shift($tableAlias);

        $config->offsetAddToArrayByPath(
            '[source][query][select]',
            [sprintf('%s.%s', $tableAlias, $this->identifierName)]
        );

        return $config;
    }
}
