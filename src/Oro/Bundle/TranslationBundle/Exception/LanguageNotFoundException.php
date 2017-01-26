<?php

namespace Oro\Bundle\TranslationBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

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
