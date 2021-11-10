<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\NavigationBundle\Provider\TitleTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;

class TitleTranslatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TitleTranslator */
    private $titleTranslator;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id, array $parameters) {
                return 'trans!' . strtr($id, $parameters);
            });

        $userConfigManager = $this->createMock(ConfigManager::class);
        $userConfigManager->expects($this->any())
            ->method('get')
            ->with('oro_navigation.title_delimiter')
            ->willReturn('-');

        $this->titleTranslator = new TitleTranslator($translator, $userConfigManager);
    }

    /**
     * @dataProvider transDataProvider
     */
    public function testTrans(string $titleTemplate, array $params, string $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            $this->titleTranslator->trans($titleTemplate, $params)
        );
    }

    public function transDataProvider(): array
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
