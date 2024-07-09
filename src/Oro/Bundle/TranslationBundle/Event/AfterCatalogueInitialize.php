<?php

namespace Oro\Bundle\TranslationBundle\Event;

use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event that fires after each messages catalogue was initialized.
 */
class AfterCatalogueInitialize extends Event
{
    public const NAME = 'oro_translation.after_catalogue_initialize';

    public function __construct(
        private MessageCatalogueInterface $catalogue
    ) {
    }

    public function getCatalogue(): MessageCatalogueInterface
    {
        return $this->catalogue;
    }
}
