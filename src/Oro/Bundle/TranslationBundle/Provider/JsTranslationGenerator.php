<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Oro\Bundle\TranslationBundle\Translation\Translator;
use Twig\Environment;

/**
 * The JS translations generator.
 */
class JsTranslationGenerator
{
    private Translator $translator;
    private Environment $twig;
    private string $template;
    private array $domains;

    public function __construct(Translator $translator, Environment $twig, string $template, array $domains)
    {
        $this->translator = $translator;
        $this->twig = $twig;
        $this->template = $template;
        $this->domains = $domains;
    }

    /**
     * Generates a string contains JS translations in JSON format.
     */
    public function generateJsTranslations(string $locale): string
    {
        $data = [
            'locale'        => $locale,
            'defaultDomain' => $this->domains ? reset($this->domains) : '',
            'translations'  => [$locale => []]
        ];

        $domainTranslations = $this->translator->getTranslations($this->domains, $locale);
        foreach ($domainTranslations as $domain => $translations) {
            $data['translations'][$locale][$domain] = $translations;
        }

        return $this->twig->render($this->template, ['json' => $data]);
    }
}
