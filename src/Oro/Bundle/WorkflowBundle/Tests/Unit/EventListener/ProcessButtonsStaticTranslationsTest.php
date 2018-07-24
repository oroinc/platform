<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Button\OperationButton;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\ActionBundle\Provider\Event\OnButtonsMatched;
use Oro\Bundle\WorkflowBundle\Button\TransitionButton;
use Oro\Bundle\WorkflowBundle\EventListener\ProcessButtonsStaticTranslations;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Translation\Helper\TransitionTranslationHelper;

class ProcessButtonsStaticTranslationsTest extends \PHPUnit\Framework\TestCase
{
    /** @var TransitionTranslationHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $translationHelper;

    /** @var ButtonProviderExtensionInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $extension;

    /** @var ButtonProvider */
    protected $buttonProvider;

    /** @var ProcessButtonsStaticTranslations */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translationHelper = $this->createMock(TransitionTranslationHelper::class);
        $this->extension = $this->createMock(ButtonProviderExtensionInterface::class);
        $this->buttonProvider = new ButtonProvider();
        $this->buttonProvider->addExtension($this->extension);

        $this->listener = new ProcessButtonsStaticTranslations($this->translationHelper);
    }

    public function testProcessButtons()
    {
        $transition1 = $this->createMock(Transition::class);

        $button1 = $this->createMock(OperationButton::class);

        $button2 = $this->createMock(TransitionButton::class);
        $button2->expects($this->once())->method('getTransition')->willReturn($transition1);

        $button3 = $this->createMock(TransitionButton::class);
        $button3->expects($this->once())->method('getTransition')->willReturn($transition1);

        $this->extension->expects($this->once())->method('find')->willReturn([
            $button1,
            $button2,
            $button3
        ]);

        $buttons = $this->buttonProvider->match(new ButtonSearchContext());

        $this->translationHelper->expects($this->once())
            ->method('processTransitionTranslations')
            ->with($transition1);

        $this->listener->processButtons(new OnButtonsMatched($buttons));
    }
}
