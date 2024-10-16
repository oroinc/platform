<?php

namespace Oro\Bundle\ApiBundle\Form\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Provides functionality to switch between API and regular form types, form type extensions and a form type guesser.
 */
class SwitchableDependencyInjectionExtension implements FormExtensionInterface
{
    private ContainerInterface $container;
    private ?string $currentExtensionName = null;
    /** @var string[] [extension name => service id, ...] */
    private array $extensionIds = [];
    /** @var FormExtensionInterface[] [extension name => FormExtensionInterface, ...] */
    private array $extensions = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Registers new form extension.
     */
    public function addExtension(string $extensionName, string $extensionServiceId): void
    {
        $this->extensionIds[$extensionName] = $extensionServiceId;
    }

    /**
     * Switches to another form extension.
     *
     * @throws \InvalidArgumentException if unknown extension name is provided
     */
    public function switchFormExtension(string $extensionName): void
    {
        $this->ensureInitialized();
        if (!isset($this->extensionIds[$extensionName])) {
            throw new \InvalidArgumentException(sprintf('Unknown extension: %s.', $extensionName));
        }

        $this->currentExtensionName = $extensionName;
    }

    #[\Override]
    public function hasType(string $name): bool
    {
        return $this->getExtension()->hasType($name);
    }

    #[\Override]
    public function getType(string $name): FormTypeInterface
    {
        return $this->getExtension()->getType($name);
    }

    #[\Override]
    public function hasTypeExtensions(string $name): bool
    {
        return $this->getExtension()->hasTypeExtensions($name);
    }

    #[\Override]
    public function getTypeExtensions(string $name): array
    {
        return $this->getExtension()->getTypeExtensions($name);
    }

    #[\Override]
    public function getTypeGuesser(): ?FormTypeGuesserInterface
    {
        return $this->getExtension()->getTypeGuesser();
    }

    private function getExtension(): FormExtensionInterface
    {
        $this->ensureInitialized();
        if (!isset($this->extensions[$this->currentExtensionName])) {
            $this->extensions[$this->currentExtensionName] = $this->container
                ->get($this->extensionIds[$this->currentExtensionName]);
        }

        return $this->extensions[$this->currentExtensionName];
    }

    private function ensureInitialized(): void
    {
        if (null === $this->currentExtensionName) {
            if (empty($this->extensionIds)) {
                throw new \RuntimeException('At least one extension must be registered.');
            }
            $this->currentExtensionName = key($this->extensionIds);
        }
    }
}
