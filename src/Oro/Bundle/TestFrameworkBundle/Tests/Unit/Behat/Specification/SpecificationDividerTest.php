<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Specification;

use Oro\Bundle\TestFrameworkBundle\Behat\Specification\SpecificationDivider;
use Symfony\Component\Filesystem\Filesystem;

class SpecificationDividerTest extends \PHPUnit_Framework_TestCase
{
    const SUITE_STUB_NAME = 'SuiteStub';

    private $featureDir;

    /**
     * @before
     */
    public function before()
    {
        $this->featureDir = sys_get_temp_dir().'/test_behat_features';
        mkdir($this->featureDir);
    }

    /**
     * @after
     */
    public function after()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->featureDir);
    }

    /**
     * @dataProvider divideSuiteProvider
     *
     * @param int $featureCount
     * @param int $divider
     * @param array $expectedSuites
     */
    public function testDivideSuite($featureCount, $divider, array $expectedSuites)
    {
        for ($i = 1; $i <= $featureCount; $i++) {
            touch($this->featureDir.'/'.$i.'.feature');
        }

        $suiteDivider = new SpecificationDivider();
        $generatedSuites = $suiteDivider->divideSuite(self::SUITE_STUB_NAME, [$this->featureDir], $divider);

        $this->assertTrue(is_array($generatedSuites));
        $this->assertCount(count($expectedSuites), $generatedSuites);

        $result = array_map(function ($suiteSettings) {
            return count($suiteSettings);
        }, $generatedSuites);
        $this->assertSame($expectedSuites, $result);
    }

    public function divideSuiteProvider()
    {
        return [
            [
                'Suite feature count' => 10,
                'Suite divider' => 3,
                'Expected feature count' => [
                    self::SUITE_STUB_NAME.'#0' => 3,
                    self::SUITE_STUB_NAME.'#1' => 3,
                    self::SUITE_STUB_NAME.'#2' => 3,
                    self::SUITE_STUB_NAME.'#3' => 1,
                ],
            ],
            [
                'Suite feature count' => 4,
                'Suite divider' => 1,
                'Expected feature count' => [
                    self::SUITE_STUB_NAME.'#0' => 1,
                    self::SUITE_STUB_NAME.'#1' => 1,
                    self::SUITE_STUB_NAME.'#2' => 1,
                    self::SUITE_STUB_NAME.'#3' => 1,
                ]
            ],
            [
                'Suite feature count' => 5,
                'Suite divider' => 7,
                'Expected feature count' => [
                    self::SUITE_STUB_NAME.'#0' => 5
                ]
            ],
        ];
    }
}
