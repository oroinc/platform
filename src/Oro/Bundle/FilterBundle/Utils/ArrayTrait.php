<?php

namespace Oro\Bundle\FilterBundle\Utils;

trait ArrayTrait
{
    /**
     * @param $array
     * @return array|bool
     */
    public function arrayFlatten($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $result = [];
        foreach ($array as $key => $value) {
            is_array($value) ?
                $result = array_merge($result, $this->arrayFlatten($value)) :
                $result[$key] = $value;
        }

        return $result;
    }
}
