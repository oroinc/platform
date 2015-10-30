<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_attachment');

        /**
         * See file types list -> http://www.freeformatter.com/mime-types-list.html
         */
        $mimeTypes
            = <<<EOF
application/msword
application/vnd.ms-excel
application/pdf
application/zip
image/gif
image/jpeg
image/png
EOF;
        $mimeTypesImage
            = <<<EOF
image/gif
image/jpeg
image/png
EOF;

        SettingsBuilder::append(
            $rootNode,
            [
                'upload_file_mime_types'  => ['value' => $mimeTypes],
                'upload_image_mime_types' => ['value' => $mimeTypesImage]
            ]
        );

        return $treeBuilder;
    }
}
