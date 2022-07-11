<?php

namespace Oro\Bundle\ConfigBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\DataTransformerInterface;
use Oro\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;
use Symfony\Component\Form\FormInterface;

/**
 * Represents a service that provides configuration of a system configuration form.
 */
interface ProviderInterface
{
    /**
     * Gets the configuration tree that is used to build API data.
     */
    public function getApiTree(?string $path = null): ?SectionDefinition;

    /**
     * Gets the configuration tree.
     */
    public function getTree(): GroupNodeDefinition;

    /**
     * Gets the configuration tree for js-tree component.
     */
    public function getJsTree(): array;

    /**
     * Gets a slice of the configuration tree in point of subtree.
     */
    public function getSubTree(string $subTreeName): GroupNodeDefinition;

    /**
     * Builds form for the given configuration tree group.
     */
    public function getForm(string $groupName, ConfigManager $configManager): FormInterface;

    /**
     * Lookup for first available groups if they are not specified yet.
     */
    public function chooseActiveGroups(?string $activeGroup, ?string $activeSubGroup): array;

    /**
     * Gets a data transformer for the given field.
     */
    public function getDataTransformer(string $key): ?DataTransformerInterface;
}
