<?php

namespace Oro\Bundle\EntityBundle\Twig;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;

class GetEntityFallbackExtension extends \Twig_Extension
{
    const NAME = 'oro_entity_fallback_extension';

    /**
     * @var EntityFallbackResolver
     */
    protected $fallbackResolver;

    /**
     * GetEntityFallbackExtension constructor.
     *
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
                'oro_get_fallback',
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
