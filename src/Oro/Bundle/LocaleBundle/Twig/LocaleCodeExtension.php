<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Formatter\LocaleCodeFormatter;

class LocaleCodeExtension extends \Twig_Extension
{
    const NAME = 'oro_locale_locale_code';

    /**
     * @var $localeCodeFormatter
     */
    protected $localeCodeFormatter;

    /**
     * @param LocaleCodeFormatter $localeCodeFormatter
     */
    public function __construct(LocaleCodeFormatter $localeCodeFormatter)
    {
        $this->localeCodeFormatter = $localeCodeFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'oro_locale_code_title',
                [$this, 'getLocaleTitleByCode'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param string $code
     * @return string
     */
    public function getLocaleTitleByCode($code)
    {
        return $this->localeCodeFormatter->formatLocaleCode($code);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
