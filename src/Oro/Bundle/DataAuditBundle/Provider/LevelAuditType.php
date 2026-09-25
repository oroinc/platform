<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Presents an audit type recorded per level: every level of the application becomes its own filterable
 * Entity Type, and a changed field is named the way the audited domain names it.
 */
class LevelAuditType implements AuditTypeInterface
{
    public function __construct(
        private readonly AbstractAuditLevelProvider $levelProvider,
        private readonly AuditFieldLabelProviderInterface $fieldLabelProvider,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function getTypes(): array
    {
        $types = [];
        foreach (array_keys($this->levelProvider->all()) as $objectClass) {
            $types[$objectClass] = $this->getLabel($objectClass);
        }

        return $types;
    }

    #[\Override]
    public function getTypeLabel(string $objectClass): ?string
    {
        return $this->levelProvider->isType($objectClass) ? $this->getLabel($objectClass) : null;
    }

    #[\Override]
    public function getFieldLabel(string $objectClass, string $field): ?string
    {
        return $this->fieldLabelProvider->getLabel($objectClass, $field);
    }

    #[\Override]
    public function getMatchingFieldGroups(string $term): array
    {
        return [$this->fieldLabelProvider->getMatchingFields($term)];
    }

    private function getLabel(string $objectClass): string
    {
        $labelKey = $this->levelProvider->getLabelKey($objectClass);
        $label = $labelKey ? $this->translator->trans($labelKey) : null;

        return $label && $label !== $labelKey ? $label : $this->levelProvider->getGenericLabel($objectClass);
    }
}
