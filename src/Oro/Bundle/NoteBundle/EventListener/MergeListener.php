<?php

namespace Oro\Bundle\NoteBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\NoteBundle\Model\MergeModes;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\ActivityListBundle\Model\MergeModes as ActivityMergeModes;

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

        if ($this->isApplicableNoteActivity($entityMetadata)) {
            $fieldMetadataOptions = [
                'display'       => true,
                'activity'      => true,
                'template'      => 'OroActivityListBundle:Merge:value.html.twig',
                'type'          => Note::ENTITY_NAME,
                'field_name'    => $this->getNoteFieldName($entityMetadata),
                'is_collection' => true,
                'label'         => $this->translator->trans($this->getNoteAlias()),
                'merge_modes'   => [MergeModes::NOTES_UNITE, MergeModes::NOTES_REPLACE]
            ];

            $fieldMetadata = new FieldMetadata($fieldMetadataOptions);
            $entityMetadata->addFieldMetadata($fieldMetadata);
        }
    }

    /**
     * @param EntityMetadata $entityMetadata
     *
     * @return bool
     */
    protected function isApplicableNoteActivity(EntityMetadata $entityMetadata)
    {
        if ($this->activityListChainProvider->isApplicableTarget($entityMetadata->getClassName(), Note::ENTITY_NAME)) {
            return true;
        }

        return false;
    }
 
    /**
     * @param EntityMetadata $entityMetadata
     *
     * @return string
     */
    protected function getNoteFieldName($entityMetadata)
    {
        $fieldsMetadata = $entityMetadata->getFieldsMetadata();

        foreach ($fieldsMetadata as $fieldName => $fieldMetadata) {
            //if there is Metadata field already exists,
            //it should be changed to custom Metadata field(with appropriate logic and Strategy)
            if ($fieldMetadata->getSourceClassName() === Note::ENTITY_NAME) {
                return $fieldName;
            }
        }

        //or generate default name
        return strtolower(str_replace('\\', '_', Note::ENTITY_NAME));
    }

    /**
     * @return string
     */
    protected function getNoteAlias()
    {
        $config = $this->configProvider->getConfig(Note::ENTITY_NAME);

        return $config->get(self::TRANSLATE_KEY);
    }
}
