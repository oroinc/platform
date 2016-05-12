<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\TranslationBundle\Provider\YamlFixer;

class YamlFixerTest extends \PHPUnit_Framework_TestCase
{
    public function testFixStrings()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'DataFixtures' .
            DIRECTORY_SEPARATOR . 'test.yml';

        $contents = file($file);
        $this->assertCount(8, $contents);

        $copyFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $copyFile = $copyFile . ltrim(uniqid(), DIRECTORY_SEPARATOR);

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

    /**
     * @param string $prefix
     *
     * @return string
     */
    protected function getTmpDir($prefix)
    {
        $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $path = $path . ltrim(uniqid($prefix), DIRECTORY_SEPARATOR);

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }
}
