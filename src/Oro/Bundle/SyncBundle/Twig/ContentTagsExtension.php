<?php

namespace Oro\Bundle\SyncBundle\Twig;

use Oro\Bundle\SyncBundle\Content\TagGeneratorChain;

class ContentTagsExtension extends \Twig_Extension
{
    /** @var TagGeneratorChain */
    protected $tagGeneratorChain;

    /**
     * @param TagGeneratorChain $tagGeneratorChain
     */
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
            new \Twig_SimpleFunction('oro_sync_get_content_tags', [$this, 'generate'])
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
        return 'oro_sync.content_tags';
    }
}
