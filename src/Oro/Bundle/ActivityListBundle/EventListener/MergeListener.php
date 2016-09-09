<?php

namespace Oro\Bundle\ActivityListBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActivityListBundle\Model\MergeModes;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class MergeListener
{
    const TRANSLATE_KEY = 'label';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var ActivityManager */
    protected $activityManager;

    /**
     * @param TranslatorInterface $translator
     * @param ConfigProvider $configProvider
     * @param ActivityManager $activityManager
     */
    public function __construct(
        TranslatorInterface $translator,
        ConfigProvider $configProvider,
        ActivityManager $activityManager
    ) {
        $this->translator = $translator;
        $this->configProvider = $configProvider;
        $this->activityManager = $activityManager;
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
                'template'      => 'OroActivityListBundle:Merge:value.html.twig',
                'is_collection' => true,
                'label'         => $this->translator->trans(
                    'oro.activity.merge.label',
                    ['%activity%' => $this->translator->trans($this->getAliasByActivityClass($type))]
                ),
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
        $types = $this->activityManager->getActivities($className);

        return array_keys($types);
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
