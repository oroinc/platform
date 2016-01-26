<?php

namespace Oro\Bundle\TagBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\TagBundle\Entity\TagManager;

use Oro\Bundle\TagBundle\Entity\Tag;

class TagTransformer implements DataTransformerInterface
{
    /**  @var TagManager */
    protected $tagManager;

    public function __construct(TagManager $tagManager)
    {
        $this->tagManager = $tagManager;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return [];
        }

        $tags  = explode(';;', $value);
        $names = [];
        foreach ($tags as $tag) {
            $tag = json_decode($tag, true);
            if (array_key_exists('name', $tag) === true) {
                $names[] = $tag['name'];
            }
        }

        if (!empty($names)) {
            return $this->tagManager->loadOrCreateTags($names);
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        $result = [];
        if (is_array($value)) {
            $result = array_map(
                function (Tag $tag) {
                    return json_encode(
                        [

                            'id'   => $tag->getId(),
                            'name' => $tag->getName(),
                        ]
                    );
                },
                $value
            );
            $result = implode(';;', $result);
        }

        return $result;
    }
}
