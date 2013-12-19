<?php

namespace Oro\Bundle\NavigationBundle\Twig;

use Oro\Bundle\NavigationBundle\Content\TagGeneratorContext;

class ContentTagsExtension extends \Twig_Extension
{
    /** @var TagGeneratorContext */
    protected $tagGeneratorContext;

    public function __construct(TagGeneratorContext $tagGeneratorContext)
    {
        $this->tagGeneratorContext = $tagGeneratorContext;
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
        return $this->tagGeneratorContext->generate($data, $includeCollectionTag);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_navigation.content_tags';
    }
}
