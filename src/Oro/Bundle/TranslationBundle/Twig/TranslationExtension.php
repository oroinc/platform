<?php

namespace Oro\Bundle\TranslationBundle\Twig;

use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;

class TranslationExtension extends \Twig_Extension
{
    const NAME = 'oro_translation';

    /**
     * @var bool
     */
    protected $debugTranslator = false;

    /**
     * @var TranslationsDatagridRouteHelper
     */
    protected $translationRouteHelper;

    /**
     * @param bool $debugTranslator
     * @param TranslationsDatagridRouteHelper $translationRouteHelper
     */
    public function __construct($debugTranslator, TranslationsDatagridRouteHelper $translationRouteHelper)
    {
        $this->debugTranslator = $debugTranslator;
        $this->translationRouteHelper = $translationRouteHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_translation_debug_translator',
                function () {
                    return $this->debugTranslator;
                }
            ),
            new \Twig_SimpleFunction('translation_grid_link', [$this->translationRouteHelper, 'generate'])
        ];
    }
}
