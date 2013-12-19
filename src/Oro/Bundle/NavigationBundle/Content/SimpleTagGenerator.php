<?php

namespace Oro\Bundle\NavigationBundle\Content;

class SimpleTagGenerator implements TagGeneratorInterface
{
    const STATIC_NAME_KEY = 'name';
    const IDENTIFIER_KEY  = 'params';

    /**
     * {@inheritdoc}
     */
    public function supports($data)
    {
        return is_array($data) && isset($data[self::STATIC_NAME_KEY]);
    }

    /**
     * {@inheritdoc}
     */
    public function generate($data, $includeCollectionTag = false)
    {
        $params = isset($data[self::IDENTIFIER_KEY]) ? $data[self::IDENTIFIER_KEY] : [];
        $tags   = [implode('_', array_merge([$data[self::STATIC_NAME_KEY]], $params))];

        if ($includeCollectionTag) {
            $tags[] = $data[self::STATIC_NAME_KEY] . self::COLLECTION_SUFFIX;
        }

        return $tags;
    }
}
