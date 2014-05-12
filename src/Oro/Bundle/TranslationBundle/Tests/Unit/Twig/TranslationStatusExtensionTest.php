<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TranslationBundle\Provider\TranslationStatisticProvider;
use Oro\Bundle\TranslationBundle\Translation\TranslationStatusInterface;
use Oro\Bundle\TranslationBundle\Twig\TranslationStatusExtension;

class TranslationStatusExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslationStatusExtension */
    protected $extension;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $cm;

    /** @var TranslationStatisticProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $sp;

    public function setUp()
    {
        $this->cm = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();
        $this->sp = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Provider\TranslationStatisticProvider')
            ->disableOriginalConstructor()->getMock();

        $this->extension = new TranslationStatusExtension($this->cm, $this->sp);
    }

    public function tearDown()
    {
        unset($this->extension, $this->sp, $this->cm);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_translation_translation_status', $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $result = $this->extension->getFunctions();

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
    }

    /**
     * @dataProvider isFreshDataProvider
     *
     * @param array  $configData
     * @param array  $statisticData
     * @param string $langCode
     * @param bool   $expectedResult
     */
    public function testIsFresh(array $configData, array $statisticData, $langCode, $expectedResult)
    {
        $this->cm->expects($this->once())->method('get')
            ->with($this->equalTo(TranslationStatusInterface::META_CONFIG_KEY))
            ->will($this->returnValue($configData));
        $this->sp->expects($this->once())->method('get')
            ->will($this->returnValue($statisticData));

        $result1 = $this->extension->isFresh($langCode);

        // second call should not fail expectation above
        // result should be cached
        $result2 = $this->extension->isFresh($langCode);

        $this->assertSame($result1, $result2);
        $this->assertSame($expectedResult, $result1);
    }

    /**
     * @return array
     */
    public function isFreshDataProvider()
    {
        return [
            'language are not installed, then needs update'                            => [
                [],
                [
                    [
                        'code'          => 'uk',
                        'lastBuildDate' => '1990-02-02 06:00:00'
                    ]
                ],
                'uk',
                false
            ],
            'language installed, stats is not contains it, then assume its up to date' => [
                ['uk' => '2014-01-14 16:38:00'],
                [],
                'uk',
                true
            ],
            'installed language needs update'                                          => [
                [
                    'uk' => ['lastBuildDate' => '2014-01-14 16:38:00'],
                    'ru' => ['lastBuildDate' => '2014-01-14 15:38:00']
                ],
                [
                    [
                        'code'          => 'ru',
                        'lastBuildDate' => '2014-01-14 18:38:00'
                    ]
                ],
                'ru',
                false
            ],
            'installed language up to date'                                            => [
                [
                    'uk' => ['lastBuildDate' => '2014-01-14 16:38:00'],
                    'ru' => ['lastBuildDate' => '2014-01-14 15:38:00']
                ],
                [
                    [
                        'code'          => 'uk',
                        'lastBuildDate' => '2014-01-14 16:38:00'
                    ]
                ],
                'uk',
                true
            ]
        ];
    }
}
