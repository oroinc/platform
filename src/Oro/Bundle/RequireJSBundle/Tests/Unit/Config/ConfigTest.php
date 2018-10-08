<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Unit\Config;

use Oro\Bundle\RequireJSBundle\Config\Config;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $properties = [
            ['mainConfig', '{json: [\'config\']}'],
            ['buildConfig', ['array' => 'config']],
            ['outputFilePath', './output/file/path'],
            ['configFilePath', './config/file/path'],
        ];

        $this->assertPropertyAccessors(new Config(), $properties);
    }
}
