<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Form\Type\ConfigCheckbox;

class GoogleSyncConfigCheckbox extends ConfigCheckbox
{
    const NAME = 'oro_config_google_imap_sync_checkbox';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
