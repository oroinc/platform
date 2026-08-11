<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Names a configuration setting recorded by the config-change audit by its location in the System
 * Configuration tree: the breadcrumb ending with the setting label, e.g.
 * "Commerce › Product › Promotions › New Arrivals › Maximum Items".
 *
 * The audit stores only the stable configuration key; the breadcrumb is built from the tree of the
 * level the record belongs to. Being a {@see AbstractAuditFieldNameProvider}, the same breadcrumb serves
 * the grid rendering and the "Data" search, in the current locale.
 */
class ConfigAuditFieldLabelProvider extends AbstractAuditFieldNameProvider
{
    /** Generic "System Configuration" tree root that carries no locating information. */
    private const array HIDDEN_TREE_GROUPS = ['platform'];

    private const string SEPARATOR = ' › ';

    public function __construct(
        private readonly ConfigBag $configBag,
        private readonly ConfigAuditLevelProvider $levelProvider,
        TranslatorInterface $translator
    ) {
        parent::__construct($translator);
    }

    /**
     * Returns the breadcrumb of a configuration setting, or null when the object class is not a
     * configuration type (so callers can fall back to their default field-name rendering).
     */
    public function getLabel(?string $objectClass, string $fieldKey): ?string
    {
        if (!$this->levelProvider->isConfigType($objectClass)) {
            return null;
        }

        // A setting that is not placed in the level's tree has no breadcrumb — show its own label.
        return $this->getName($objectClass, $fieldKey) ?? $this->getFieldLabel($fieldKey);
    }

    /**
     * Configuration keys whose breadcrumb contains the term, so the "Data" filter finds a setting by any
     * part of the path the user sees: "Promotions", "New Arrivals" and "Maximum Items" all match the
     * same setting. Configuration keys are globally unique, hence no object class scoping.
     *
     * @return string[]
     */
    public function getMatchingFieldKeys(string $term): array
    {
        return $this->matchFields($term)['fields'];
    }

    #[\Override]
    protected function getObjectClasses(): array
    {
        return array_keys($this->levelProvider->all());
    }

    #[\Override]
    protected function buildNamesFor(string $objectClass): array
    {
        $tree = $this->configBag->getTreeRoot($this->levelProvider->getTreeForClass($objectClass));

        $breadcrumbs = [];
        if (\is_array($tree)) {
            $this->collect($tree, [], $breadcrumbs);
        }

        return $breadcrumbs;
    }

    /**
     * Walks a configuration tree once. A node is either a group (has "children") that extends the path,
     * or a plain field key that ends it.
     *
     * @param string[] $path translated titles of the groups walked so far
     * @param array<string, string> $breadcrumbs [config key => breadcrumb]
     */
    private function collect(array $nodes, array $path, array &$breadcrumbs): void
    {
        foreach ($nodes as $name => $node) {
            if (\is_string($node)) {
                $breadcrumbs[$node] = $this->buildBreadcrumb($path, $node);
                continue;
            }
            if (!\is_array($node) || !isset($node['children'])) {
                continue;
            }

            $this->collect(
                $node['children'],
                \in_array($name, self::HIDDEN_TREE_GROUPS, true)
                    ? $path
                    : [...$path, $this->getGroupTitle((string)$name)],
                $breadcrumbs
            );
        }
    }

    /**
     * @param string[] $path
     */
    private function buildBreadcrumb(array $path, string $fieldKey): string
    {
        $label = $this->getFieldLabel($fieldKey);
        // Do not repeat the label when the innermost group already carries the same title.
        if ($path && end($path) === $label) {
            array_pop($path);
        }
        $path[] = $label;

        return implode(self::SEPARATOR, $path);
    }

    private function getGroupTitle(string $name): string
    {
        $group = $this->configBag->getGroupsNode($name);

        return $this->trans(\is_array($group) ? ($group['title'] ?? null) : null, $name);
    }

    private function getFieldLabel(string $fieldKey): string
    {
        $field = $this->configBag->getFieldsRoot($fieldKey);

        return $this->trans(\is_array($field) ? ($field['options']['label'] ?? null) : null, $fieldKey);
    }

    private function trans(mixed $key, string $fallback): string
    {
        return $key ? $this->translator->trans((string)$key) : $fallback;
    }
}
