<?php

namespace Oro\Bundle\TagBundle\Form\Transformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Form\DataTransformerInterface;

class TagTransformer implements DataTransformerInterface
{
    /**  @var TagManager */
    protected $tagManager;

    /**  @var PropertyAccessor */
    protected $propertyAccessor;

    public function __construct(TagManager $tagManager)
    {
        $this->tagManager = $tagManager;
        $this->propertyAccessor = new PropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return new ArrayCollection();
        }

        $tags  = explode(';;', $value);
        $names = [];
        foreach ($tags as $tag) {
            $tag = json_decode($tag, true);
            if ($tag && array_key_exists('name', $tag) === true) {
                $names[] = $tag['name'];
            }
        }

        if (!empty($names)) {
            return new ArrayCollection($this->tagManager->loadOrCreateTags($names));
        }

        return new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        $result = [];
        if (is_array($value)) {
            $result = array_map(
                function ($tag) {
                    return json_encode(
                        [

                            'id'   => $this->propertyAccessor->getValue($tag, 'id'),
                            'name' => $this->propertyAccessor->getValue($tag, 'name'),
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
