<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Nelmio\Alice\Fixtures\Parser\Methods\Yaml;

class AliceYamlParser extends Yaml
{
    /**
     * {@inheritDoc}
     */
    public function parse($file)
    {
        $data = parent::parse($file);
        unset($data['dependencies']);

        return $data;
    }
}
