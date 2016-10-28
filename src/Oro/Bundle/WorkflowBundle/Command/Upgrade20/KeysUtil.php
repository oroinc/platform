<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20;

use Symfony\Component\Translation\Util\ArrayConverter;

class KeysUtil
{
    public static function expandToTree(array $array)
    {
        return ArrayConverter::expandToTree($array);
    }

    /**
     * comes form \Symfony\Component\Translation\Loader\ArrayLoader
     * Flattens an nested array of translations.
     *
     * The scheme used is:
     *   'key' => array('key2' => array('key3' => 'value'))
     * Becomes:
     *   'key.key2.key3' => 'value'
     *
     * This function takes an array by reference and will modify it
     *
     * @param array &$messages The array that will be flattened
     * @param array $subnode Current subnode being parsed, used internally for recursive calls
     * @param string $path Current path being parsed, used internally for recursive calls
     */
    public static function flatten(array &$messages, array $subnode = null, $path = null)
    {
        if (null === $subnode) {
            $subnode = &$messages;
        }
        foreach ($subnode as $key => $value) {
            if (is_array($value)) {
                $nodePath = $path ? $path . '.' . $key : $key;
                self::flatten($messages, $value, $nodePath);
                if (null === $path) {
                    unset($messages[$key]);
                }
            } elseif (null !== $path) {
                $messages[$path . '.' . $key] = $value;
            }
        }
    }
}
