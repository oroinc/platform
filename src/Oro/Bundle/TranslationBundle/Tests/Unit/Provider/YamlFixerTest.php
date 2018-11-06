<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Provider\YamlFixer;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Yaml\Yaml;

class YamlFixerTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    public function testFixStrings()
    {
        $file = realpath(__DIR__ . '/../DataFixtures/test.yml');

        $contents = file($file);
        $this->assertCount(8, $contents);

        $copyFile = $this->getTempFile('yaml_fixer');

        copy($file, $copyFile);
        YamlFixer::fixStrings($copyFile);

        try {
            $result = (bool)Yaml::parse(file_get_contents($copyFile));
        } catch (\Exception $e) {
            $result = false;
        }

        $this->assertTrue($result);

        $contents = file($copyFile);
        unlink($copyFile);

        $this->assertCount(7, $contents);
    }
}
