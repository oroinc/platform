<?php

namespace Oro\Bundle\LayoutBundle\Twig;

class ArrayExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('omit', [$this, 'omitFilter']),
        ];
    }

    /**
     * Returns a copy of an array, filtered to omit the blacklisted keys
     *
     * <pre>
     *  {% set items = { 'apple': 'fruit', 'orange': 'fruit' } %}
     *
     *  {% set items = items|omit(['apple']) %}
     *
     *  {# items now contains { 'orange': 'fruit' } #}
     * </pre>
     *
     * @param array $arr         The source array
     * @param array $excludeKeys The list of keys to be omitted from the array
     *
     * @return array The filtered array
     *
     * @throws \Twig_Error_Runtime
     */
    public function omitFilter($arr, $excludeKeys)
    {
        if (!is_array($arr) || !is_array($excludeKeys)) {
            throw new \Twig_Error_Runtime('The omit filter only works with arrays or hashes.');
        }

        return array_diff_key($arr, array_flip($excludeKeys));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_layout_array';
    }
}
