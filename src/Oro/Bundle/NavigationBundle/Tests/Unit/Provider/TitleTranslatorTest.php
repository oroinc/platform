<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Oro\Bundle\NavigationBundle\Provider\TitleTranslator;

class TitleTranslatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $userConfigManager;

    /** @var TitleTranslator */
    protected $titleTranslator;

    protected function setUp()
    {
        $this->translator        = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $this->userConfigManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator->expects($this->any())
            ->method('trans')
            ->will(
                $this->returnCallback(
                    function ($id, array $parameters) {
                        return 'trans!' . strtr($id, $parameters);
                    }
                )
            );
        $this->userConfigManager->expects($this->any())
            ->method('get')
            ->with('oro_navigation.title_delimiter')
            ->will($this->returnValue('-'));

        $this->titleTranslator = new TitleTranslator($this->translator, $this->userConfigManager);
    }

    /**
     * @dataProvider transDataProvider
     */
    public function testTrans($titleTemplate, $params, $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            $this->titleTranslator->trans($titleTemplate, $params)
        );
    }

    public function transDataProvider()
    {
        return [
            [
                '',
                [],
                ''
            ],
            [
                'contact %name% - customers',
                ['%name%' => 'John'],
                'trans!contact John - trans!customers'
            ]
        ];
    }
}
