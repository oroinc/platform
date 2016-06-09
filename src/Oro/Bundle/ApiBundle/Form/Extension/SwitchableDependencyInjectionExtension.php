<?php

namespace Oro\Bundle\ApiBundle\Form\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormExtensionInterface;

class SwitchableDependencyInjectionExtension implements FormExtensionInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var string */
    protected $currentExtensionName;

    /** @var string[] [extension name => service id, ...] */
    protected $extensionIds = [];

    /** @var FormExtensionInterface[] [extension name => FormExtensionInterface, ...] */
    protected $extensions = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Registers new form extension.
     *
     * @param string $extensionName
     * @param string $extensionServiceId
     */
    public function addExtension($extensionName, $extensionServiceId)
    {
        $this->extensionIds[$extensionName] = $extensionServiceId;
    }

    /**
     * Switches to another form extension.
     *
     * @param string $extensionName
     *
     * @throws \InvalidArgumentException if unknown extension name is provided
     */
    public function switchFormExtension($extensionName)
    {
        $this->ensureInitialized();
        if (!isset($this->extensionIds[$extensionName])) {
            throw new \InvalidArgumentException(
                sprintf('Unknown extension: %s.', $extensionName)
            );
        }

        $this->currentExtensionName = $extensionName;
    }

    /**
     * {@inheritdoc}
     */
    public function hasType($name)
    {
        return $this->getExtension()->hasType($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getType($name)
    {
        return $this->getExtension()->getType($name);
    }

    /**
     * {@inheritdoc}
     */
    public function hasTypeExtensions($name)
    {
        return $this->getExtension()->hasTypeExtensions($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeExtensions($name)
    {
        return $this->getExtension()->getTypeExtensions($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeGuesser()
    {
        return $this->getExtension()->getTypeGuesser();
    }

    /**
     * @return FormExtensionInterface
     */
    protected function getExtension()
    {
        $this->ensureInitialized();
        if (!isset($this->extensions[$this->currentExtensionName])) {
            $this->extensions[$this->currentExtensionName] = $this->container
                ->get($this->extensionIds[$this->currentExtensionName]);
        }

        return $this->extensions[$this->currentExtensionName];
    }

    protected function ensureInitialized()
    {
        if (null === $this->currentExtensionName) {
            if (empty($this->extensionIds)) {
                throw new \RuntimeException('At least one extension must be registered.');
            }
            $this->currentExtensionName = key($this->extensionIds);
        }
    }
}
