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
     * @param bool  $processNestedData
     *
     * @return array
     */
    public function generate($data, $includeCollectionTag = false, $processNestedData = true)
    {
        // enforce plain array should returns
        return array_values($this->tagGeneratorChain->generate($data, $includeCollectionTag, $processNestedData));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_navigation.content_tags';
    }
}
