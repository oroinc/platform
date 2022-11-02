<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Datagrid\Action;

use Oro\Bundle\ActionBundle\Datagrid\Action\ButtonWidgetAction;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Symfony\Contracts\Translation\TranslatorInterface;

class ButtonWidgetActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ButtonWidgetAction */
    private $action;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->action = new ButtonWidgetAction($this->translator);
    }

    /**
     * @dataProvider getOptionsProvider
     */
    public function testGetOptions(array $config, string $link, ?string $title, ?string $translatedTitle)
    {
        $this->action->setOptions(ActionConfiguration::create($config));

        if ($translatedTitle) {
            $this->translator->expects($this->any())
                ->method('trans')
                ->with($title)
                ->willReturn($translatedTitle);
        }

        /** @var \ArrayAccess $options */
        $options = $this->action->getOptions();

        $this->assertInstanceOf(ActionConfiguration::class, $options);
        $this->assertCount(count($config) + 1, $options);
        $this->assertArrayHasKey('link', $options);
        $this->assertEquals($link, $options['link']);

        if (array_key_exists('dialogOptions', $options['options'])) {
            $this->assertArrayHasKey('title', $options['options']['dialogOptions']);
            $this->assertEquals($translatedTitle, $options['options']['dialogOptions']['title']);
        }
    }

    public function getOptionsProvider(): array
    {
        $link = 'http://example.com/';
        $title = 'Test Dialog Title';

        return [
            'with dialog title' => [
                'options' => [
                    'link' => $link,
                    'options' => [
                        'dialogOptions' => [
                            'title' => $title
                        ]
                    ]
                ],
                'link' => $link,
                'title' => $title,
                'translatedTitle' => 'Translated Title',
            ],
            'without dialog title' => [
                'options' => [
                    'link' => $link,
                    'options' => [],
                ],
                'link' => $link,
                'title' => null,
                'translatedTitle' => null,
            ],
        ];
    }
}
