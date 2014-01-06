<?php

namespace Oro\Bundle\TranslationBundle\Translation;

interface TranslationStatusInterface
{
    /**
     * Setting key for config manager
     */
    const CONFIG_KEY = 'oro_translation.available_translations';

    /**
     * Translation pack was not downloaded yet
     */
    const STATUS_NEW        = 1;

    /**
     * Translation pack downloaded, but did not enabled for usage.
     */
    const STATUS_DOWNLOADED = 2;

    /**
     * Translation pack downloaded and available for usage
     */
    const STATUS_ENABLED    = 3;
}
