<?php

namespace Oro\Bundle\TranslationBundle\Layout\DataProvider;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides layer for layouts to use translator as DataProvider
 */
class TranslatorProvider
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param string|null $id
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    public function getTrans(?string $id, array $parameters = [], string $domain = null, string $locale = null)
    {
        return $this->translator->trans((string) $id, $parameters, $domain, $locale);
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->translator->getLocale();
    }
}
