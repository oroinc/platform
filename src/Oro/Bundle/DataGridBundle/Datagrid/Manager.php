<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

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
    /** @var Builder */
    protected $datagridBuilder;

    /** @var ConfigurationProviderInterface */
    protected $configurationProvider;

    /** @var RequestParameterBagFactory */
    protected $parametersFactory;

    /**
     * Constructor
     *
     * @param ConfigurationProviderInterface $configurationProvider
     * @param Builder                        $builder
     * @param RequestParameterBagFactory     $parametersFactory
     */
    public function __construct(
        ConfigurationProviderInterface $configurationProvider,
        Builder $builder,
        RequestParameterBagFactory $parametersFactory
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->datagridBuilder       = $builder;
        $this->parametersFactory     = $parametersFactory;
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
            throw new \InvalidArgumentException('$parameters must be an array or instance of ParameterBag.');
        }

        $configuration = $this->getConfigurationForGrid($name);

        return $this->datagridBuilder->build($configuration, $parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function getDatagridByRequestParams($name, array $additionalParameters = [])
    {
        $parameters = $this->parametersFactory->createParameters($name);
        $parameters->add($additionalParameters);

        return $this->getDatagrid($name, $parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigurationForGrid($name)
    {
        return $this->configurationProvider->getConfiguration($name);
    }
}
