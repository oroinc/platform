<?php

namespace Oro\Bundle\TranslationBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * Event that fires after each messages catalogue was dumped to cache.
 */
class AfterCatalogueDump extends Event
{
    public const NAME = 'oro_translation.after_catalogue_dump';

    /** @var MessageCatalogueInterface */
    private $catalogue;

    /**
     * @param MessageCatalogueInterface $catalogue
     */
    public function __construct(MessageCatalogueInterface $catalogue)
    {
        $this->catalogue = $catalogue;
    }

    /**
     * @return MessageCatalogueInterface
     */
    public function getCatalogue(): MessageCatalogueInterface
    {
        return $this->catalogue;
    }
}
