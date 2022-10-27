<?php

namespace Oro\Bundle\SegmentBundle\Grid;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Grid\BuilderAwareInterface;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * The provider for configuration of datagrids used to show segments.
 */
class ConfigurationProvider implements ConfigurationProviderInterface, BuilderAwareInterface
{
    /** @var SegmentDatagridConfigurationBuilder */
    private $builder;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var DatagridConfiguration[] */
    private $configuration = [];

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
        $id = (int)substr($gridName, \strlen(Segment::GRID_PREFIX));
        if (!$id) {
            throw new \RuntimeException(sprintf('The segment ID not found in the "%s" grid name.', $gridName));
        }

        if (empty($this->configuration[$gridName])) {
            $segment = $this->doctrine->getRepository(Segment::class)->find($id);

            $this->builder->setGridName($gridName);
            $this->builder->setSource($segment);

            $this->configuration[$gridName] = $this->builder->getConfiguration();
        }

        return $this->configuration[$gridName];
    }

    /**
     * Checks whether a segment grid ready for displaying.
     */
    public function isConfigurationValid(string $gridName): bool
    {
        try {
            $this->getConfiguration($gridName);
        } catch (InvalidConfigurationException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getBuilder(): DatagridConfigurationBuilder
    {
        return $this->builder;
    }
}
