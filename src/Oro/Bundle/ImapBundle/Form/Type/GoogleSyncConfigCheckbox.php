<?php

namespace Oro\Bundle\ImapBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Form\Type\ConfigCheckbox;

/**
 * Definition of checkbox type for Google Applicaiton
 * IMAP synchronization enable/disable checkbox
 */
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

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return $this->getName();
    }
}
