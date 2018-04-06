<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Oro\Bundle\ConfigBundle\Exception\UnexpectedTypeException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigBag
{
    /** @var array */
    protected $config;

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param array              $config
     * @param ContainerInterface $container
     */
    public function __construct(array $config, ContainerInterface $container)
    {
        $this->config    = $config;
        $this->container = $container;
    }

    /**
     * Gets config
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns specified data transformer
     *
     * @param $key
     *
     * @return DataTransformerInterface|null
     *
     * @throws UnexpectedTypeException if transformer is not instance of DataTransformerInterface
     */
    public function getDataTransformer($key)
    {
        if (!isset($this->config['fields'][$key]['data_transformer'])) {
            return null;
        }

        $transformer = $this->container->get($this->config['fields'][$key]['data_transformer']);
        if ($transformer !== null && !$transformer instanceof DataTransformerInterface) {
            throw new UnexpectedTypeException(
                $transformer,
                'Oro\Bundle\ConfigBundle\Config\DataTransformerInterface'
            );
        }

        return $transformer;
    }

    /**
     * Gets fields root
     *
     * @param string $node field node name
     *
     * @return mixed
     */
    public function getFieldsRoot($node)
    {
        if (isset($this->config[ProcessorDecorator::FIELDS_ROOT]) &&
            isset($this->config[ProcessorDecorator::FIELDS_ROOT][$node])
        ) {
            return $this->config[ProcessorDecorator::FIELDS_ROOT][$node];
        }

        return false;
    }

    /**
     * Gets tree root
     *
     * @param $treeName
     *
     * @return mixed
     */
    public function getTreeRoot($treeName)
    {
        if (isset($this->config[ProcessorDecorator::TREE_ROOT]) &&
            isset($this->config[ProcessorDecorator::TREE_ROOT][$treeName])
        ) {
            return $this->config[ProcessorDecorator::TREE_ROOT][$treeName];
        }

        return false;
    }

    /**
     * Gets groups node
     *
     * @param $name
     *
     * @return mixed
     */
    public function getGroupsNode($name)
    {
        if (isset($this->config[ProcessorDecorator::GROUPS_NODE]) &&
            isset($this->config[ProcessorDecorator::GROUPS_NODE][$name])
        ) {
            return $this->config[ProcessorDecorator::GROUPS_NODE][$name];
        }

        return false;
    }
}
