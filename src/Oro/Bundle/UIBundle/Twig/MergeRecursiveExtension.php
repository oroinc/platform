<?php

namespace Oro\Bundle\UIBundle\Twig;

class MergeRecursiveExtension extends \Twig_Extension
{
    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'merge_recursive',
                ['Oro\Bundle\UIBundle\Tools\ArrayUtils', 'arrayMergeRecursiveDistinct']
            )
        ];
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'oro_ui.merge_recursive';
    }
}
