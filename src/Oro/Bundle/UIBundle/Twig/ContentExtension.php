<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager;

class ContentExtension extends \Twig_Extension
{
    /**
     * @var ContentProviderManager
     */
    protected $contentProviderManager;

    /**
     * @param ContentProviderManager $contentProviderManager
     */
    public function __construct(ContentProviderManager $contentProviderManager)
    {
        $this->contentProviderManager = $contentProviderManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'oro_get_content' => new \Twig_Function_Method(
                $this,
                'getContent',
                array(
                    'is_safe' => array('html')
                )
            )
        );
    }

    /**
     * Get content.
     *
     * @param array $additionalContent
     * @param array $keys
     * @return array
     */
    public function getContent(array $additionalContent = null, array $keys = null)
    {
        $content = $this->contentProviderManager->getContent($keys);
        if ($additionalContent) {
            $content = array_merge($content, $additionalContent);
        }
        if ($keys) {
            $content = array_intersect_key($content, array_combine($keys, $keys));
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_ui.content';
    }
}
