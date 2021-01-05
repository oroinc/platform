<?php

namespace Oro\Bundle\SegmentBundle\Grid;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Grid\BuilderAwareInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * The provider for configuration of datagrids for segments.
 */
class ConfigurationProvider implements ConfigurationProviderInterface, BuilderAwareInterface
{
    /** @var SegmentDatagridConfigurationBuilder */
    private $builder;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var DatagridConfiguration[] */
    private $configuration = [];

    /**
     * @param SegmentDatagridConfigurationBuilder $builder
     * @param ManagerRegistry                     $doctrine
     */
    public function __construct(
        SegmentDatagridConfigurationBuilder $builder,
        ManagerRegistry $doctrine
    ) {
        $this->builder = $builder;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(string $gridName): bool
    {
        return $this->builder->isApplicable($gridName);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(string $gridName): DatagridConfiguration
    {
        $id = intval(substr($gridName, strlen(Segment::GRID_PREFIX)));
        if (!$id) {
            throw new \RuntimeException(
                sprintf('Segment id not found in "%s" gridName.', $gridName)
            );
        }

        if (empty($this->configuration[$gridName])) {
            $segmentRepository = $this->doctrine->getRepository('OroSegmentBundle:Segment');
            $segment = $segmentRepository->find($id);

            $this->builder->setGridName($gridName);
            $this->builder->setSource($segment);

            $this->configuration[$gridName] = $this->builder->getConfiguration();
        }

        return $this->configuration[$gridName];
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

    /**
     * {@inheritdoc}
     */
    public function getBuilder()
    {
        return $this->builder;
    }
}
