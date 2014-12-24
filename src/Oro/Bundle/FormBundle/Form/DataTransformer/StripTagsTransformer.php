<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class StripTagsTransformer implements DataTransformerInterface
{
    const DELIMITER = ',';

    /**
     * @var string
     */
    protected $allowableTags;

    /**
     * @param string|null $allowableTags
     */
    public function __construct($allowableTags = null)
    {
        $this->allowableTags = $this->stripAllowableTags($allowableTags);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return strip_tags($value, $this->allowableTags);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $value;
    }

    /**
     * @param $allowableTags
     * @return mixed
     */
    public function stripAllowableTags($allowableTags)
    {
        /** strip attributes */
        $allowableTags = preg_replace('(\[.*?\]|\s+)', '', $allowableTags);

        /** strip or condition */
        $allowableTags = preg_replace('(\/)', self::DELIMITER, $allowableTags);

        if (empty($allowableTags)) {
            return null;
        }

        $tags = array_map(
            function ($tag) {
                return sprintf('<%s>', $tag);
            },
            explode(self::DELIMITER, $allowableTags)
        );

        return implode($tags);
    }
}
