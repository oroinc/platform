<?php

namespace Oro\Bundle\TranslationBundle\Twig;

class TranslationExtension extends \Twig_Extension
{
    const NAME = 'oro_translation';

    /**
     * @var bool
     */
    protected $debugTranslator = false;

    /**
     * @param bool $debugTranslator
     */
    public function __construct($debugTranslator)
    {
        $this->debugTranslator = $debugTranslator;
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
            )
        ];
    }
}
