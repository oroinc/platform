<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

use Nelmio\ApiDocBundle\Parser\ParserInterface;

class FieldConfigParser implements ParserInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(array $item)
    {
        return is_a($item['class'], ApiDocFieldsDefinition::class, true);
    }

    /**
     * {@inheritdoc}
     */
    public function parse(array $item)
    {
        $result = [];
        /** @var FieldDoc $fieldConfig */
        foreach ($item['options']['data'] as $fieldConfig) {
            $result[$fieldConfig->getFieldName()] = $fieldConfig->toArray();
        }

        return $result;
    }
}
