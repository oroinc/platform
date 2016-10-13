<?php

namespace Oro\Bundle\EntityBundle\Twig;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;

class EntityFallbackExtension extends \Twig_Extension
{
    const NAME = 'oro_entity_fallback_extension';

    /**
     * @var EntityFallbackResolver
     */
    protected $fallbackResolver;

    /**
     * @param $fallbackResolver
     */
    public function __construct(EntityFallbackResolver $fallbackResolver)
    {
        $this->fallbackResolver = $fallbackResolver;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_entity_fallback_value',
                [$this->fallbackResolver, 'getFallbackValue']
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
