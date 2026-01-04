<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Form\Type\ConfigCheckbox;

/**
 * Definition of checkbox type for Google Applicaiton
 * IMAP synchronization enable/disable checkbox
 */
class GoogleSyncConfigCheckbox extends ConfigCheckbox
{
    public const NAME = 'oro_config_google_imap_sync_checkbox';

    #[\Override]
    public function getName()
    {
        return self::NAME;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return $this->getName();
    }
}
