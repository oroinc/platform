<?php

namespace Oro\Bundle\TagBundle\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Formatter\TypeFormatterInterface;

class TagsTypeFormatter implements TypeFormatterInterface
{
    const TYPE_TAGS = 'tags';

    /**
     * {@inheritdoc}
     */
    public function formatType($value, $type)
    {
        if ($type === self::TYPE_TAGS) {
            return $this->formatTagsType($value);
        }

        throw new InvalidArgumentException(sprintf('Invalid type "%s" format for tags.', $type));
    }

    /**
     * Format array with tags to the export grid format.
     *
     * @param array $value
     *
     * @return string
     */
    protected function formatTagsType($value = [])
    {
        $value = (array)$value;
        $names = array_map(
            function ($tag) {
                return $tag['name'];
            },
            $value
        );

        return implode(',', $names);
    }
}
