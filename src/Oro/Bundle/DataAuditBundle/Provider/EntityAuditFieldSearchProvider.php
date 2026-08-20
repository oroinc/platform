<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Names audited ENTITY fields the way the audit grid does, so the "Data" filter finds a change by the
 * label the user sees ("Primary Email") even though the audit stores the technical field name ("email").
 *
 * The label is read from the very entity configuration attribute the grid renders
 * (``oro_field_config_value(class, field, 'label')``); being a {@see AbstractAuditFieldNameProvider} it is
 * translated for the current locale.
 */
class EntityAuditFieldSearchProvider extends AbstractAuditFieldNameProvider
{
    private const string ENTITY_SCOPE = 'entity';

    public function __construct(
        private readonly AuditConfigProvider $auditConfigProvider,
        private readonly ConfigManager $entityConfigManager,
        TranslatorInterface $translator
    ) {
        parent::__construct($translator);
    }

    /**
     * @return array{classes: string[], fields: string[]}
     */
    public function getMatchingFields(string $term): array
    {
        return $this->matchFields($term);
    }

    #[\Override]
    protected function getObjectClasses(): array
    {
        return $this->auditConfigProvider->getAllAuditableEntities();
    }

    #[\Override]
    protected function buildNamesFor(string $objectClass): array
    {
        $auditableFields = array_flip($this->auditConfigProvider->getAuditableFields($objectClass));
        if (!$auditableFields) {
            return [];
        }

        $names = [];
        foreach ($this->entityConfigManager->getConfigs(self::ENTITY_SCOPE, $objectClass) as $fieldConfig) {
            $fieldId = $fieldConfig->getId();
            if (!$fieldId instanceof FieldConfigId || !isset($auditableFields[$fieldId->getFieldName()])) {
                continue;
            }

            $label = (string)$fieldConfig->get('label');
            if ('' !== $label) {
                $names[$fieldId->getFieldName()] = $this->translator->trans($label);
            }
        }

        return $names;
    }
}
