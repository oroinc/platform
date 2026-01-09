<?php

namespace Oro\Bundle\TranslationBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Thrown when a requested language is not found in the system.
 *
 * This exception is raised when attempting to access or retrieve a language by its locale code
 * that does not exist in the translation system. It includes the HTTP NOT_FOUND status code
 * to indicate the resource was not found.
 */
class LanguageNotFoundException extends \Exception
{
    /**
     * @param string $locale
     */
    public function __construct($locale)
    {
        parent::__construct(sprintf('Language "%s" not found', $locale), Response::HTTP_NOT_FOUND);
    }
}
