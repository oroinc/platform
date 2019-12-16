<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\AttachmentBundle\Helper\FieldConfigHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Oro\Bundle\UIBundle\Twig\UiExtension;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Render Multi File fields of related entities in separated sections.
 */
class MultiFileBlockListener
{
    /** @var ConfigProvider */
    private $entityConfigProvider;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param ConfigProvider $configProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(ConfigProvider $configProvider, TranslatorInterface $translator)
    {
        $this->entityConfigProvider = $configProvider;
        $this->translator = $translator;
    }

    /**
     * @param BeforeFormRenderEvent $event
     */
    public function onBeforeFormRender(BeforeFormRenderEvent $event)
    {
        if (!$event->getEntity()) {
            return;
        }

        $className = ClassUtils::getClass($event->getEntity());

        $fieldConfigs = $this->entityConfigProvider->getIds($className);
        if (!$fieldConfigs) {
            return;
        }

        //Turn array data to DTO to make possible to manipulate blocks
        $scrollData = new ScrollData($event->getFormData());
        //MultiFile section renders before Additional
        $sectionPriority = UiExtension::ADDITIONAL_SECTION_PRIORITY - 1;

        foreach ($fieldConfigs as $fieldConfig) {
            $fieldName = $fieldConfig->getFieldName();
            if (!FieldConfigHelper::isMultiField($fieldConfig)) {
                continue;
            }
            $config = $this->entityConfigProvider->getConfig($className, $fieldName);
            $newBlockKey = $fieldName . '_block_section';

            $scrollData->addNamedBlock(
                $newBlockKey,
                $this->translator->trans($config->get('label')),
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
}
