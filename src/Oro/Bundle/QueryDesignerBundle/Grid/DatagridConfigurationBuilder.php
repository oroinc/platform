<?php

namespace Oro\Bundle\QueryDesignerBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;

/**
 * Builds a data grid configuration based on a query definition created by the query designer.
 */
class DatagridConfigurationBuilder
{
    /** @var DatagridConfigurationQueryConverter */
    protected $converter;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var AbstractQueryDesigner */
    protected $source;

    /** @var string */
    protected $gridName;

    /**
     * @throws InvalidConfigurationException
     */
    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        VirtualRelationProviderInterface $virtualRelationProvider,
        DoctrineHelper $doctrineHelper,
        DatagridGuesser $datagridGuesser,
        EntityNameResolver $entityNameResolver
    ) {
        $this->doctrineHelper = $doctrineHelper;

        $this->converter = new DatagridConfigurationQueryConverter(
            $functionProvider,
            $virtualFieldProvider,
            $virtualRelationProvider,
            $doctrineHelper,
            $datagridGuesser,
            $entityNameResolver
        );
    }

    public function setSource(AbstractQueryDesigner $source)
    {
        $this->source = $source;
    }

    /**
     * @param string $gridName
     */
    public function setGridName($gridName)
    {
        $this->gridName = $gridName;
    }

    /**
     * Return a datagrid configuration
     *
     * @return DatagridConfiguration
     *
     * @throws \InvalidArgumentException
     */
    public function getConfiguration()
    {
        if (empty($this->gridName)) {
            throw new \InvalidArgumentException('Grid name not configured');
        }

        if (!$this->source) {
            throw new \InvalidArgumentException('Source is missing');
        }

        return $this->converter->convert($this->gridName, $this->source);
    }

    /**
     * @param string $gridName
     *
     * @return bool
     */
    public function isApplicable($gridName)
    {
        return false;
    }
}
