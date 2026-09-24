<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Oro\Bundle\DataAuditBundle\Model\MenuAuditFieldName;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Names a changed property of a menu item by the entity configuration label the menu form shows.
 */
class MenuAuditFieldLabelProvider extends AbstractAuditFieldNameProvider implements
    AuditFieldLabelProviderInterface
{
    private const string AUDIT_LABEL_PREFIX = 'oro.dataaudit.menu.field.';
    private const string ENTITY_SCOPE = 'entity';

    public function __construct(
        private readonly MenuAuditLevelProvider $levelProvider,
        private readonly ConfigManager $entityConfigManager,
        private readonly string $menuUpdateClass,
        TranslatorInterface $translator
    ) {
        parent::__construct($translator);
    }

    #[\Override]
    public function getLabel(?string $objectClass, string $field): ?string
    {
        if (!$this->levelProvider->isType($objectClass)) {
            return null;
        }

        $fieldName = MenuAuditFieldName::parse($field);
        $label = $this->getName($objectClass, $fieldName->getProperty()) ?? $fieldName->getProperty();
        $localization = $fieldName->getLocalization();

        return null !== $localization ? sprintf('%s (%s)', $label, $localization) : $label;
    }

    #[\Override]
    protected function getObjectClasses(): array
    {
        return array_keys($this->levelProvider->all());
    }

    #[\Override]
    protected function buildNamesFor(string $objectClass): array
    {
        $names = [];
        foreach ($this->entityConfigManager->getConfigs(self::ENTITY_SCOPE, $this->menuUpdateClass) as $fieldConfig) {
            $fieldId = $fieldConfig->getId();
            if (!$fieldId instanceof FieldConfigId) {
                continue;
            }

            $property = $fieldId->getFieldName();
            $label = $this->getAuditLabel($property) ?? (string)$fieldConfig->get('label');
            if ('' !== $label) {
                $names[$property] = $this->translator->trans($label);
            }
        }

        return $names;
    }

    private function getAuditLabel(string $property): ?string
    {
        $key = self::AUDIT_LABEL_PREFIX . $property;

        return $this->translator->trans($key) !== $key ? $key : null;
    }
}
