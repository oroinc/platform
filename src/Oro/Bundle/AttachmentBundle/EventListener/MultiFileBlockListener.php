<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\AttachmentBundle\Helper\FieldConfigHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamilyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Oro\Bundle\UIBundle\Event\BeforeViewRenderEvent;
use Oro\Bundle\UIBundle\Twig\UiExtension;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Render Multi File fields of related entities in separated sections.
 */
class MultiFileBlockListener
{
    // default priority for view pages
    const ADDITIONAL_SECTION_PRIORITY = 1200;

    /** @var AttributeManager */
    private $attributeManager;

    /** @var ConfigProvider */
    private $entityConfigProvider;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(ConfigProvider $configProvider, TranslatorInterface $translator)
    {
        $this->entityConfigProvider = $configProvider;
        $this->translator = $translator;
    }

    public function setAttributeManager(AttributeManager $attributeManager): void
    {
        $this->attributeManager = $attributeManager;
    }

    public function onBeforeValueRender(ValueRenderEvent $event)
    {
        if (FieldConfigHelper::isMultiField($event->getFieldConfigId())) {
            $event->setFieldVisibility(false);
        } elseif ($this->isFileOrImageField($event->getFieldConfigId()->getFieldType())) {
            $event->setFieldViewValue([
                'template' => '@OroAttachment/Twig/dynamicField.html.twig',
                'fieldConfigId' => $event->getFieldConfigId(),
                'entity' => $event->getEntity(),
                'value' => $event->getFieldValue(),
            ]);
        }
    }

    public function onBeforeViewRender(BeforeViewRenderEvent $event)
    {
        if (!$event->getEntity()) {
            return;
        }

        $className = ClassUtils::getClass($event->getEntity());
        $fieldConfigs = $this->getConfigs($event->getEntity());
        if (!$fieldConfigs) {
            return;
        }

        //Turn array data to DTO to make possible to manipulate blocks
        $scrollData = new ScrollData($event->getData());
        //MultiFile section renders before Additional
        $sectionPriority = self::ADDITIONAL_SECTION_PRIORITY - 1;

        foreach ($fieldConfigs as $fieldConfig) {
            $fieldName = $fieldConfig->getFieldName();
            $config = $this->entityConfigProvider->getConfig($className, $fieldName);
            $fieldLabel = $this->translator->trans((string) $config->get('label'));

            $blockKey = $fieldName . '_block_section';

            $scrollData->addNamedBlock(
                $blockKey,
                $fieldLabel,
                $sectionPriority
            );

            $html = $event->getTwigEnvironment()->render(
                '@OroAttachment/Twig/dynamicField.html.twig',
                [
                    'data' => [
                        'entity' => $event->getEntity(),
                        'fieldConfigId' => $fieldConfig,
                        'fieldLabel' => $fieldLabel
                    ]
                ]
            );
            $subblockId = $scrollData->addSubBlock($blockKey);
            $scrollData->addSubBlockData($blockKey, $subblockId, $html, $fieldName);
        }

        $event->setData($scrollData->getData());
    }

    public function onBeforeFormRender(BeforeFormRenderEvent $event)
    {
        if (!$event->getEntity()) {
            return;
        }

        $className = ClassUtils::getClass($event->getEntity());

        $fieldConfigs = $this->getConfigs($event->getEntity());
        if (!$fieldConfigs) {
            return;
        }

        //Turn array data to DTO to make possible to manipulate blocks
        $scrollData = new ScrollData($event->getFormData());
        //MultiFile section renders before Additional
        $sectionPriority = UiExtension::ADDITIONAL_SECTION_PRIORITY - 1;

        foreach ($fieldConfigs as $fieldConfig) {
            $fieldName = $fieldConfig->getFieldName();
            $config = $this->entityConfigProvider->getConfig($className, $fieldName);
            $newBlockKey = $fieldName . '_block_section';

            $scrollData->addNamedBlock(
                $newBlockKey,
                $this->translator->trans((string) $config->get('label')),
                $sectionPriority
            );

            $scrollData->moveFieldToBlock($fieldName, $newBlockKey);
        }

        // Removes Additional section if no subblocks left
        if ($scrollData->hasBlock(UiExtension::ADDITIONAL_SECTION_KEY)
            && $scrollData->isEmptyBlock(UiExtension::ADDITIONAL_SECTION_KEY)) {
            $scrollData->removeNamedBlock(UiExtension::ADDITIONAL_SECTION_KEY);
        }

        $event->setFormData($scrollData->getData());
    }

    /**
     * @param object $entity
     *
     * @return FieldConfigModel[]
     */
    private function getConfigs(object $entity): array
    {
        $configManager = $this->entityConfigProvider->getConfigManager();
        $className = ClassUtils::getClass($entity);

        $ids = $this->entityConfigProvider->getIds($className);
        $multiIds = array_filter($ids, fn (FieldConfigId $id) => FieldConfigHelper::isMultiField($id));

        if ($entity instanceof AttributeFamilyAwareInterface && $entity->getAttributeFamily()) {
            $family = $entity->getAttributeFamily();
            $familyFilter = function (FieldConfigId $id) use ($configManager, $family, $className) {
                $config = $configManager->getFieldConfig('attribute', $className, $id->getFieldName());

                return $config->get('is_attribute')
                    ? $this->attributeManager->getAttributeByFamilyAndName($family, $id->getFieldName())
                    : true;
            };

            $multiIds = array_filter($multiIds, $familyFilter);
        }

        return $multiIds;
    }

    private function isFileOrImageField(string $type): bool
    {
        return in_array($type, [FieldConfigHelper::FILE_TYPE, FieldConfigHelper::IMAGE_TYPE], true);
    }
}
