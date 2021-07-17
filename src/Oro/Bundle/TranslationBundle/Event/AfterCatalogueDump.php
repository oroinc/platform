<?php

namespace Oro\Bundle\TranslationBundle\Event;

use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event that fires after each messages catalogue was dumped to cache.
 */
class AfterCatalogueDump extends Event
{
    public const NAME = 'oro_translation.after_catalogue_dump';

    /** @var MessageCatalogueInterface */
    private $catalogue;

    public function __construct(MessageCatalogueInterface $catalogue)
    {
        $this->catalogue = $catalogue;
    }

    public function getCatalogue(): MessageCatalogueInterface
    {
        return $this->catalogue;
    }
}
