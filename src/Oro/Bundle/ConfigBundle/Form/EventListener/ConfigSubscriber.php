<?php

namespace Oro\Bundle\ConfigBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\File as HttpFile;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ConfigBundle\Form\Type\ConfigFileType;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param ConfigManager $configManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ConfigManager $configManager, DoctrineHelper $doctrineHelper)
    {
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SUBMIT => 'preSubmit'
        );
    }

    /**
     * Preset default values if default checkbox set
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        foreach ($data as $key => $val) {
            if (!empty($val['use_parent_scope_value'])) {
                $valueFileTypeClass = get_class($form->get($key)->get('value')->getConfig()->getType()->getInnerType());
                $parentValue = $this->configManager->get(
                    str_replace(
                        ConfigManager::SECTION_VIEW_SEPARATOR,
                        ConfigManager::SECTION_MODEL_SEPARATOR,
                        $key
                    ),
                    true
                );

                if (ConfigFileType::class === $valueFileTypeClass) {
                    $file = is_int($parentValue) ? $this->getHttpFileByEntityFileId($parentValue) : null;
                    $data[$key]['value'] = [
                        'file' => $file,
                        'emptyFile' => (bool) $file
                    ];
                } else {
                    $data[$key]['value'] = $parentValue;
                }
            }
        }

        $event->setData($data);
    }

    /**
     * @param int $id
     * @return HttpFile|null
     */
    private function getHttpFileByEntityFileId($id)
    {
        $file = $this->doctrineHelper->getEntityRepositoryForClass(File::class)->find($id);

        return $file ? $file->getFile() : null;

    }
}
