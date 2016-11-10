<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Provider;

use Oro\Bundle\ActionBundle\Model\ButtonInterface;
use Oro\Bundle\ActionBundle\Model\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;

class ButtonProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ButtonProvider */
    protected $buttonProvider;

    /** @var ButtonProviderExtensionInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $buttonExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->buttonProvider = new ButtonProvider();
        $this->buttonExtension = $this->getMock(ButtonProviderExtensionInterface::class);
        $this->buttonProvider->addExtension($this->buttonExtension);
    }

    /**
     * @dataProvider buttonProvider
     *
     * @param array $input
     * @param array $output
     */
    public function testFindAll(array $input, array $output)
    {
        /** @var ButtonSearchContext $searchContext */
        $searchContext = $this->getMock(ButtonSearchContext::class);
        $this->buttonExtension->expects($this->once())
            ->method('find')
            ->with($searchContext)
            ->willReturn($input);

        $this->assertSame($output, $this->buttonProvider->findAll($searchContext));
    }

    /**
     * @return array
     */
    public function buttonProvider()
    {
        $button1 = $this->getButton(1);
        $button2 = $this->getButton(2);
        $button3 = $this->getButton(3);

        return [
            [
                'input' => [],
                'output' => []
            ],
            [
                'input' => [$button2],
                'output' => [$button2]
            ],
            [
                'input' => [$button2, $button1, $button3],
                'output' => [$button1, $button2, $button3]
            ],
            [
                'input' => [$button3, $button3, $button2],
                'output' => [$button2, $button3, $button3]
            ]
        ];
    }

    /**
     * @param int $order
     * @return ButtonInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getButton($order)
    {
        $button = $this->getMock(ButtonInterface::class);
        $button->method('getOrder')->willReturn($order);

        return $button;
    }
}
