<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use Oro\Bundle\ConfigBundle\Model\Data\Transformer\TransformerInterface;
use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;

class ConfigBag
{
    /** @var array */
    protected $config;

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     * @param array              $config
     */
    public function __construct(array $config = [], ContainerInterface $container = null)
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
     * Sets config
     *
     * @param array $config
     *
     * @return self
     */
    public function setConfig(array $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Returns specified data transformer
     *
     * @param $key
     *
     * @return TransformerInterface|null
     *
     * @throws UnexpectedTypeException if transformer is not instance of TransformerInterface
     */
    public function getDataTransformer($key)
    {
        $transformer = null;
        if (!empty($this->config['fields'][$key]['data_transformer'])) {
            $transformer = $this->container->get($this->config['fields'][$key]['data_transformer']);
            if ($transformer !== null && !$transformer instanceof TransformerInterface) {
                throw new UnexpectedTypeException(
                    $transformer,
                    'Oro\Bundle\ConfigBundle\Model\Data\Transformer\TransformerInterface'
                );
            }
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
