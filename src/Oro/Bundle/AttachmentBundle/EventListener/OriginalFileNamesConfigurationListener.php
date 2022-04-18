<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Warn user about original file names option impact on change.
 */
class OriginalFileNamesConfigurationListener
{
    private const ATTACHMENT_ORIGINAL_FILE_NAMES_ENABLED = 'oro_attachment.original_file_names_enabled';

    private Session $session;

    private TranslatorInterface $translator;

    public function __construct(Session $session, TranslatorInterface $translator)
    {
        $this->session = $session;
        $this->translator = $translator;
    }

    public function afterUpdate(ConfigUpdateEvent $event): void
    {
        $changeSet = $event->getChangeSet();
        foreach ($changeSet as $configKey => $change) {
            if ($configKey === self::ATTACHMENT_ORIGINAL_FILE_NAMES_ENABLED) {
                $this->session->getFlashBag()->add(
                    'warning',
                    $this->translator->trans('oro.attachment.config.notice.storage_check_space')
                );
            }
        }
    }
}
