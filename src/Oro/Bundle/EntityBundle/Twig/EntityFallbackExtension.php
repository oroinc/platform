<?php

namespace Oro\Bundle\EntityBundle\Twig;

use Oro\Component\DependencyInjection\ServiceLink;

class EntityFallbackExtension extends \Twig_Extension
{
    const NAME = 'oro_entity_fallback_extension';

    /**
     * @var ServiceLink
     */
    protected $fallbackResolverLink;

    /**
     * @param ServiceLink $fallbackResolverLink
     */
    public function __construct(ServiceLink $fallbackResolverLink)
    {
        $this->fallbackResolverLink = $fallbackResolverLink;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_entity_fallback_value',
                [$this->fallbackResolverLink->getService(), 'getFallbackValue']
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
