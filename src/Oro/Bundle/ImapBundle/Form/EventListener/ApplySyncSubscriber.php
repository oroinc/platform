<?php

namespace Oro\Bundle\ImapBundle\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ApplySyncSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT   => ['applySync', 5]
        ];
    }

    /**
     * Merge folder and set sync
     *
     * @param FormEvent $event
     */
    public function applySync(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if ($data && array_key_exists('folders', $data) && is_array($data['folders'])) {
            /** @var UserEmailOrigin $origin */
            $origin = $form->getData();

            if ($origin !== null && $origin->getId() !== null) {
                $this->applySyncEnabled($origin->getRootFolders(), $data['folders']);

                $form->remove('folders');
                unset($data['folders']);
            }
        } else {
            $form->remove('folders');
        }
        $event->setData($data);
    }

    /**
     * @param ArrayCollection $folders
     * @param array $data
     */
    protected function applySyncEnabled($folders, $data)
    {
        /** @var EmailFolder $folder */
        foreach ($folders as $folder) {
            $f = array_filter($data, function ($item) use ($folder) {
                return $folder->getFullName() === $item['fullName'];
            });

            $matched = reset($f);
            if ($matched) {
                $syncEnabled = array_key_exists('syncEnabled', $matched);
                $folder->setSyncEnabled($syncEnabled);

                if (array_key_exists('subFolders', $matched) && $folder->hasSubFolders()) {
                    $this->applySyncEnabled($folder->getSubFolders(), $matched['subFolders']);
                }
            }
        }
    }
}
