<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class StripTagsTransformer implements DataTransformerInterface
{
    const DELIMITER = ',';

    /**
     * @var string|null
     */
    protected $allowableTags;

    /**
     * @param string|null $allowableTags
     */
    public function __construct($allowableTags = null)
    {
        if ($allowableTags) {
            $this->allowableTags = $this->prepareAllowedTagsList($allowableTags);
        }
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
     * Prepare list of allowable tags based on tinymce allowable tags syntax.
     *
     * @param string $allowableTags
     * @return string
     */
    protected function prepareAllowedTagsList($allowableTags)
    {
        /** strip attributes */
        $allowableTags = preg_replace('(\[.*?\]|\s+)', '', $allowableTags);

        /** strip or condition */
        $allowableTags = preg_replace('(\/)', self::DELIMITER, $allowableTags);

        $cleanTags = str_replace(self::DELIMITER, '', $allowableTags);
        if (empty($cleanTags)) {
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
