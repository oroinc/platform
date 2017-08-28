<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

use Nelmio\ApiDocBundle\Parser\ParserInterface;

/**
 * Extracts fields definition from ApiDoc annotation.
 *
 * Example:
 * <code>
 *     output={
 *          "class"="Your\Namespace\Class",
 *          "fields"={
 *              {"name"="aField", "dataType"="string", "description"="The 'aField' field description."}
 *          }
 *     }
 * </code>
 */
class ApiDocAnnotationParser implements ParserInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(array $item)
    {
        return
            isset($item['fields'])
            && is_array($item['fields']);
    }

    /**
     * {@inheritdoc}
     */
    public function parse(array $item)
    {
        $result = [];
        foreach ($item['fields'] as $fieldData) {
            $fieldName = $fieldData['name'];
            unset($fieldData['name']);

            if (!array_key_exists('required', $fieldData)) {
                $fieldData['required'] = false;
            }

            $result[$fieldName] = $fieldData;
        }

        return $result;
    }
}
