<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\MenuUpdate\MenuUpdateInheritedStatePropagator;
use Oro\Bundle\DataAuditBundle\Model\AuditEntry;
use Oro\Bundle\DataAuditBundle\Model\MenuAuditFieldName;
use Oro\Bundle\DataAuditBundle\Model\MenuAuditObject;
use Oro\Bundle\DataAuditBundle\Model\MenuAuditValueNormalizer;
use Oro\Bundle\DataAuditBundle\Provider\MenuAuditLevelProvider;
use Oro\Bundle\DataAuditBundle\Provider\MenuItemNameProvider;
use Oro\Bundle\DataAuditBundle\Service\AuditEntryRecorder;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * Records what an administrator changed in a menu: one Data Audit entry per changed menu item, whose entity
 * type is the level the menu was customized on and whose fields are the properties of the item that changed.
 */
class MenuUpdateAuditListener
{
    private const array IGNORED_FIELDS = ['id', 'key', 'menu', 'scope', 'custom', 'synthetic', 'serialized_data'];
    private const array ITEM_KEY_FIELDS = ['parentKey'];
    private const string TITLE_FIELD = 'titles';

    public function __construct(
        private readonly AuditEntryRecorder $auditEntryRecorder,
        private readonly MenuUpdateChangeCollector $changeCollector,
        private readonly MenuAuditLevelProvider $levelProvider,
        private readonly MenuAuditValueNormalizer $valueNormalizer,
        private readonly MenuUpdateInheritedStatePropagator $inheritedState,
        private readonly MenuItemNameProvider $itemNameProvider
    ) {
    }

    public function onMenuUpdateChange(): void
    {
        $transactionId = UUIDGenerator::v4();
        foreach ($this->changeCollector->release() as $group) {
            foreach ($group['items'] as $itemKey => $item) {
                $this->record($group, (string)$itemKey, $item, $transactionId);
            }
        }

        $this->inheritedState->reset();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function record(array $group, string $itemKey, array $item, string $transactionId): void
    {
        $inheritedState = Audit::ACTION_CREATE === $item['action']
            ? $this->inheritedState->getInheritedState($item['menuUpdate'])
            : null;

        $auditObject = new MenuAuditObject(
            $this->levelProvider->getClassForScope($group['scope']),
            $group['menu'],
            $group['scope']->getId(),
            $itemKey
        );

        $action = $this->resolveAction($item, $inheritedState);
        $entry = new AuditEntry(
            $auditObject->getObjectClass(),
            $auditObject->getObjectId(),
            $this->getObjectName($group['menu'], $group['scope'], $item['menuUpdate']),
            $action
        );

        foreach ($item['changeSet'] as $field => [$old, $new]) {
            $fieldName = MenuAuditFieldName::parse((string)$field);
            $property = $fieldName->getProperty();
            if (\in_array($property, self::IGNORED_FIELDS, true)) {
                continue;
            }

            $old ??= $this->getInheritedValue($inheritedState, $fieldName, $itemKey);

            $isItemKey = \in_array($property, self::ITEM_KEY_FIELDS, true);
            if ($isItemKey && Audit::ACTION_REMOVE !== $item['action']) {
                $old = $this->getTopLevelKey($old, $group['menu'], Audit::ACTION_CREATE === $action);
                $new = $this->getTopLevelKey($new, $group['menu'], Audit::ACTION_REMOVE === $action);
            }

            if ($this->valueNormalizer->isSame($old, $new)) {
                continue;
            }

            if ($isItemKey) {
                $old = null !== $old ? $this->getItemName($old, $group) : null;
                $new = null !== $new ? $this->getItemName($new, $group) : null;
            }

            $change = $this->valueNormalizer->normalize($old, $new);
            $entry->addChange((string)$field, $change['old'], $change['new'], $change['type']);
        }

        $this->auditEntryRecorder->record($entry, $transactionId);
    }

    private function getInheritedValue(?array $inheritedState, MenuAuditFieldName $field, string $itemKey): mixed
    {
        if (null === $inheritedState) {
            return null;
        }

        $inherited = $inheritedState[$field->getProperty()] ?? null;
        if (\is_array($inherited)) {
            $inherited = $inherited[(string)$field->getLocalization()] ?? null;
        }

        if (null === $inherited && null === $field->getLocalization() && self::TITLE_FIELD === $field->getProperty()) {
            $inherited = $this->itemNameProvider->getMenuTitle($itemKey);
        }

        return $inherited;
    }

    private function resolveAction(array $item, ?array $inheritedState): string
    {
        if (Audit::ACTION_REMOVE === $item['action']) {
            return $item['menuUpdate']->isCustom() ? Audit::ACTION_REMOVE : Audit::ACTION_UPDATE;
        }

        if (Audit::ACTION_CREATE !== $item['action']) {
            return $item['action'];
        }

        return $item['menuUpdate']->isCustom() && null === $inheritedState
            ? Audit::ACTION_CREATE
            : Audit::ACTION_UPDATE;
    }

    private function getObjectName(string $menu, Scope $scope, MenuUpdateInterface $menuUpdate): string
    {
        $name = $menu . ' / ' . $this->itemNameProvider->getName($menuUpdate);
        $target = $this->levelProvider->getTargetName($scope);
        if (null !== $target) {
            $name = sprintf('%s (%s)', $name, $target);
        }

        return mb_substr($name, 0, AbstractAudit::OBJECT_NAME_MAX_LENGTH);
    }

    /**
     * The item a menu item is nested into, where an item nested into nothing is nested into the menu itself.
     * An item that did not exist yet, or does not exist any more, is nested into nothing at all.
     */
    private function getTopLevelKey(mixed $key, string $menu, bool $hasNoItem): ?string
    {
        $key = (string)$key;
        if ('' !== $key) {
            return $key;
        }

        return $hasNoItem ? null : $menu;
    }

    private function getItemName(string $key, array $group): string
    {
        if ($key === $group['menu']) {
            return $key;
        }

        return isset($group['items'][$key])
            ? $this->itemNameProvider->getName($group['items'][$key]['menuUpdate'])
            : $this->itemNameProvider->getNameForKey($group['menu'], $group['scope'], $key);
    }
}
