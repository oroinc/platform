<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * Interface representing translation message catalogue sanitizer.
 */
interface MessageCatalogueSanitizerInterface
{
    public function sanitizeCatalogue(MessageCatalogueInterface  $catalogue): void;

    /**
     * @return array|SanitizationErrorInformation[]
     */
    public function getSanitizationErrors(): array;
}
