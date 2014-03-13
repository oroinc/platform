<?php

namespace Oro\Bundle\SegmentBundle\Grid;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;

class ConfigurationProvider implements ConfigurationProviderInterface
{
    const GRID_PREFIX = 'oro_segment_grid_';

    /**
     * @var FunctionProviderInterface
     */
    protected $functionProvider;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var DatagridConfiguration
     */
    private $configuration = null;

    /**
     * Constructor
     *
     * @param FunctionProviderInterface $functionProvider
     * @param ManagerRegistry           $doctrine
     */
    public function __construct(FunctionProviderInterface $functionProvider, ManagerRegistry $doctrine)
    {
        $this->functionProvider = $functionProvider;
        $this->doctrine         = $doctrine;
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
            $id                = intval(substr($gridName, strlen(self::GRID_PREFIX)));
            $segmentRepository = $this->doctrine->getRepository('OroSegmentBundle:Segment');
            $segment            = $segmentRepository->find($id);
            $builder           = new SegmentDatagridConfigurationBuilder(
                $gridName,
                $segment,
                $this->functionProvider,
                $this->doctrine
            );

            $this->configuration = $builder->getConfiguration();
        }

        return $this->configuration;
    }

    /**
     * Check whether a segment grid ready for displaying
     *
     * @param string $gridName
     *
     * @return bool
     */
    public function isConfigurationValid($gridName)
    {
        try {
            $this->getConfiguration($gridName);
        } catch (InvalidConfigurationException $invalidConfigEx) {
            return false;
        }

        return true;
    }
}
