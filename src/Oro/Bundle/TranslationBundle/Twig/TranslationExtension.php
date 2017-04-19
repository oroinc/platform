<?php

namespace Oro\Bundle\TranslationBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;

class TranslationExtension extends \Twig_Extension
{
    const NAME = 'oro_translation';

    /** @var ContainerInterface */
    protected $container;

    /** @var bool */
    protected $debugTranslator = false;

    /**
     * @param ContainerInterface $container
     * @param bool               $debugTranslator
     */
    public function __construct(ContainerInterface $container, $debugTranslator)
    {
        $this->debugTranslator = $debugTranslator;
        $this->container = $container;
    }

    /**
     * @return TranslationsDatagridRouteHelper
     */
    protected function getTranslationsDatagridRouteHelper()
    {
        return $this->container->get('oro_translation.helper.translation_route');
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
            new \Twig_SimpleFunction('oro_translation_debug_translator', [$this, 'isDebugTranslator']),
            new \Twig_SimpleFunction('translation_grid_link', [$this, 'getTranslationGridLink'])
        ];
    }

    /**
     * @return bool
     */
    public function isDebugTranslator()
    {
        return $this->debugTranslator;
    }

    /**
     * @param array $filters
     * @param int   $referenceType
     *
     * @return string
     */
    public function getTranslationGridLink(array $filters = [], $referenceType = RouterInterface::ABSOLUTE_PATH)
    {
        return $this->getTranslationsDatagridRouteHelper()->generate($filters, $referenceType);
    }
}
