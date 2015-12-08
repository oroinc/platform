<?php

namespace Oro\Bundle\ActivityListBundle\EventListener;

use Oro\Bundle\ActivityListBundle\Model\MergeModes;
use Symfony\Component\Translation\TranslatorInterface;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class MergeListener
{
    const TRANSLATE_KEY = 'plural_label';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var ActivityListChainProvider */
    protected $activityListChainProvider;

    /**
     * @param TranslatorInterface $translator
     * @param ConfigProvider $configProvider
     * @param ActivityListChainProvider $activityListChainProvider
     */
    public function __construct(
        TranslatorInterface $translator,
        ConfigProvider $configProvider,
        ActivityListChainProvider $activityListChainProvider
    ) {
        $this->translator = $translator;
        $this->configProvider = $configProvider;
        $this->activityListChainProvider = $activityListChainProvider;
    }

    /**
     * @param EntityMetadataEvent $event
     */
    public function onBuildMetadata(EntityMetadataEvent $event)
    {
        $entityMetadata = $event->getEntityMetadata();
        $types = $this->getAvailableActivityTypes($entityMetadata);

        foreach ($types as $type) {
            $fieldMetadataOptions = [
                'display'       => true,
                'activity'      => true,
                'type'          => $type,
                'field_name'    => $this->getFieldNameByActivityClassName($type),
                'is_collection' => true,
                'label'         => $this->translator->trans($this->getAliasByActivityClass($type)),
                'merge_modes'   => [MergeModes::ACTIVITY_UNITE, MergeModes::ACTIVITY_REPLACE]
            ];

            $fieldMetadata = new FieldMetadata($fieldMetadataOptions);
            $entityMetadata->addFieldMetadata($fieldMetadata);
        }
    }

    /**
     * @param EntityMetadata $entityMetadata
     *
     * @return array
     */
    protected function getAvailableActivityTypes(EntityMetadata $entityMetadata)
    {
        $className = $entityMetadata->getClassName();
        $types = [];
        foreach ($this->activityListChainProvider->getSupportedActivities() as $type) {
            if ($this->activityListChainProvider->isApplicableTarget($className, $type)) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * @param string $className
     *
     * @return string
     */
    protected function getFieldNameByActivityClassName($className)
    {
        return strtolower(str_replace('\\', '_', $className));
    }

    /**
     * @param string $className
     *
     * @return string
     */
    protected function getAliasByActivityClass($className)
    {
        $config = $this->configProvider->getConfig($className);

        return $config->get(self::TRANSLATE_KEY);
    }
}
