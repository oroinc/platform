<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class StripTagsTransformer implements DataTransformerInterface
{
    const DELIMITER = ',';

    /**
     * @var string|null
     */
    protected $allowedTags;

    /**
     * @param string|null $allowedTags
     */
    public function __construct($allowedTags = null)
    {
        if ($allowedTags) {
            $this->allowedTags = $this->prepareAllowedTagsList($allowedTags);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return strip_tags($value, $this->allowedTags);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $value;
    }

    /**
     * Prepare list of allowable tags based on tinymce valid tags syntax.
     *
     * @param string $allowedTags
     * @return string
     */
    protected function prepareAllowedTagsList($allowedTags)
    {
        /** strip attributes */
        $allowedTags = preg_replace('(\[.*?\]|\s+)', '', $allowedTags);

        /** strip or condition */
        $allowedTags = preg_replace('(\/)', self::DELIMITER, $allowedTags);

        $cleanTags = str_replace(self::DELIMITER, '', $allowedTags);
        if (empty($cleanTags)) {
            return null;
        }

        $tags = array_map(
            function ($tag) {
                return sprintf('<%s>', $tag);
            },
            explode(self::DELIMITER, $allowedTags)
        );

        return implode($tags);
    }
}
