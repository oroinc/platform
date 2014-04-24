<?php

namespace Oro\Bundle\ChartBundle\Model;

interface DataTransformerInterface
{
    /**
     * @param array $config
     * @param array $sourceData
     * @return array
     */
    public function transform(array $config, array $sourceData);
}
