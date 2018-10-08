<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Unit\Datagrid\Action;

use Oro\Bundle\ActionBundle\Datagrid\Action\ButtonWidgetAction;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Symfony\Component\Translation\TranslatorInterface;

class ButtonWidgetActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ButtonWidgetAction */
    protected $action;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    protected $translator;

    protected function setUp()
    {
        $this->translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $this->action = new ButtonWidgetAction($this->translator);
    }

    protected function tearDown()
    {
        unset($this->action, $this->translator);
    }

    /**
     * @dataProvider getOptionsProvider
     *
     * @param array $config
     * @param string $link
     * @param string $title
     * @param string $translatedTitle
     */
    public function testGetOptions(array $config, $link, $title, $translatedTitle)
    {
        $this->action->setOptions(ActionConfiguration::create($config));

        if ($translatedTitle) {
            $this->translator->expects($this->any())
                ->method('trans')
                ->with($title)
                ->will($this->returnValue($translatedTitle));
        }

        /** @var \ArrayAccess $options */
        $options = $this->action->getOptions();

        $this->assertInstanceOf('Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration', $options);
        $this->assertCount(count($config) + 1, $options);
        $this->assertArrayHasKey('link', $options);
        $this->assertEquals($link, $options['link']);

        if (array_key_exists('dialogOptions', $options['options'])) {
            $this->assertArrayHasKey('title', $options['options']['dialogOptions']);
            $this->assertEquals($translatedTitle, $options['options']['dialogOptions']['title']);
        }
    }

    /**
     * @return array
     */
    public function getOptionsProvider()
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
