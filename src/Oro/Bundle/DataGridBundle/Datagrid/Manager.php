<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;

/**
 * Class Manager
 *
 * @package Oro\Bundle\DataGridBundle\Datagrid
 *
 * Responsibility of this class is to store raw config data, prepare configs for datagrid builder.
 * Public interface returns datagrid object prepared by builder using config
 */
class Manager implements ManagerInterface
{
    /**
     * This flag may be used by callers of this class and extensions to decide are they required for current request
     */
    const REQUIRE_ALL_EXTENSIONS = 'require_all_extensions';

    /** @var Builder */
    protected $datagridBuilder;

    /** @var ConfigurationProviderInterface */
    protected $configurationProvider;

    /** @var RequestParameterBagFactory */
    protected $parametersFactory;

    /** @var NameStrategyInterface */
    protected $nameStrategy;

    /**
     * Constructor
     *
     * @param ConfigurationProviderInterface $configurationProvider
     * @param Builder                        $builder
     * @param RequestParameterBagFactory     $parametersFactory
     * @param NameStrategyInterface          $nameStrategy
     */
    public function __construct(
        ConfigurationProviderInterface $configurationProvider,
        Builder $builder,
        RequestParameterBagFactory $parametersFactory,
        NameStrategyInterface $nameStrategy
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->datagridBuilder       = $builder;
        $this->parametersFactory     = $parametersFactory;
        $this->nameStrategy          = $nameStrategy;
    }

    /**
     * {@inheritDoc}
     */
    public function getDatagrid($name, $parameters = null)
    {
        if (null === $parameters) {
            $parameters = new ParameterBag();
        } elseif (is_array($parameters)) {
            $parameters = new ParameterBag($parameters);
        } elseif (!$parameters instanceof ParameterBag) {
            throw new InvalidArgumentException('$parameters must be an array or instance of ParameterBag.');
        }

        $configuration = $this->getConfigurationForGrid($name);

        $datagrid = $this->datagridBuilder->build($configuration, $parameters);

        return $datagrid;
    }

    /**
     * Used to generate unique id for grid on page
     *
     * @param string $name
     *
     * @return string
     */
    public function getDatagridUniqueName($name)
    {
        return $this->nameStrategy->getGridUniqueName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getDatagridByRequestParams($name, array $additionalParameters = [])
    {
        $gridScope = $this->nameStrategy->parseGridScope($name);
        if (!$gridScope) {
            // In case if grid has scope in config we should use it to get grid parameters properly
            $configuration = $this->getConfigurationForGrid($name);
            $scope = $configuration->offsetGetOr('scope');
            if ($scope) {
                $name = $this->nameStrategy->buildGridFullName($name, $scope);
            }
        }

        $uniqueName = $this->getDatagridUniqueName($name);
        $parameters = $this->parametersFactory->createParameters($uniqueName);

        /**
         * In case of custom relation - $gridScope will be present.
         * So we need to check for additional parameters (pager, sorter, etc.) by gridName (without scope).
         * E.g. 'uniqueName' can be like 'entity-relation-grid:OroAcme_Bundle_AcmeBundle_Entity_AcmeEntit-relOneToMany'
         *  so parameters by 'uniqueName' will contain 'class_name', 'field_name', 'id'
         *  and parameters by 'gridName' (entity-relation-grid) will contain '_pager', '_sort_by', etc.
         * In such cases we'll merge them together, otherwise pagination and sorters will not work.
         */
        if ($gridScope) {
            $gridName   = $this->nameStrategy->parseGridName($name);
            $additional = $this->parametersFactory->createParameters($gridName)->all();
            if ($additional) {
                $additionalParameters = array_merge($additionalParameters, $additional);
            }
        }

        $parameters->add($additionalParameters);

        return $this->getDatagrid($name, $parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigurationForGrid($name)
    {
        $gridName = $this->nameStrategy->parseGridName($name);
        $result = $this->configurationProvider->getConfiguration($gridName);

        $gridScope = $this->nameStrategy->parseGridScope($name);
        if ($gridScope) {
            $result->offsetSet('scope', $gridScope);
        }

        return $result;
    }
}
