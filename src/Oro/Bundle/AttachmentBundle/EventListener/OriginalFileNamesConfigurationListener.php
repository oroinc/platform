<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Warn user about original file names option impact on change.
 */
class OriginalFileNamesConfigurationListener
{
    private const ATTACHMENT_ORIGINAL_FILE_NAMES_ENABLED = 'oro_attachment.original_file_names_enabled';

    public function __construct(private RequestStack $requestStack, private TranslatorInterface $translator)
    {
    }

    public function afterUpdate(ConfigUpdateEvent $event): void
    {
        $changeSet = $event->getChangeSet();
        $request = $this->requestStack->getCurrentRequest();
        foreach ($changeSet as $configKey => $change) {
            if ($configKey === self::ATTACHMENT_ORIGINAL_FILE_NAMES_ENABLED
                && null !== $request
                && $request->hasSession()) {
                $request->getSession()->getFlashBag()->add(
                    'warning',
                    $this->translator->trans('oro.attachment.config.notice.storage_check_space')
                );
            }
        }
    }
}
