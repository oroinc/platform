<?php

namespace Oro\Bundle\NavigationBundle\Twig;

use Oro\Bundle\NavigationBundle\Content\TagGeneratorChain;

class ContentTagsExtension extends \Twig_Extension
{
    /** @var TagGeneratorChain */
    protected $tagGeneratorChain;

    public function __construct(TagGeneratorChain $tagGeneratorChain)
    {
        $this->tagGeneratorChain = $tagGeneratorChain;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'oro_navigation_get_content_tags' => new \Twig_Function_Method($this, 'generate', [])
        ];
    }

    /**
     * @param mixed $data
     * @param bool  $includeCollectionTag
     *
     * @return array
     */
    public function generate($data, $includeCollectionTag = false)
    {
        return $this->tagGeneratorChain->generate($data, $includeCollectionTag);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_navigation.content_tags';
    }
}
