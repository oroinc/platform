<?php

namespace Oro\Bundle\SyncBundle\Content;

class SimpleTagGenerator implements TagGeneratorInterface
{
    const STATIC_NAME_KEY = 'name';
    const IDENTIFIER_KEY  = 'params';
    const NESTED_DATA_KEY = 'children';

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
    public function generate($data, $includeCollectionTag = false, $processNestedData = false)
    {
        $params = isset($data[self::IDENTIFIER_KEY]) ? $data[self::IDENTIFIER_KEY] : [];
        $tags   = [implode('_', array_merge([$data[self::STATIC_NAME_KEY]], $params))];

        if ($processNestedData && !empty($data[self::NESTED_DATA_KEY]) && is_array($data[self::NESTED_DATA_KEY])) {
            foreach ($data[self::NESTED_DATA_KEY] as $child) {
                if ($this->supports($child)) {
                    // allowed one nested level
                    $tags = array_merge($tags, $this->generate($child));
                }
            }
        }

        if ($includeCollectionTag) {
            $tags[] = $data[self::STATIC_NAME_KEY] . self::COLLECTION_SUFFIX;
        }

        return $tags;
    }
}
