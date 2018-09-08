<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Nelmio\Alice\Fixtures\Parser\Methods\Yaml;

/**
 * Expanded Alice yaml file parsed that clears 'dependencies' and 'initial' parameters.
 */
class AliceYamlParser extends Yaml
{
    /**
     * {@inheritDoc}
     */
    public function parse($file)
    {
        $data = parent::parse($file);
        unset($data['dependencies']);
        unset($data['initial']);

        return $data;
    }
}
