<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\ActionBundle\Provider\Event\OnButtonsMatched;
use Oro\Bundle\WorkflowBundle\Button\AbstractTransitionButton;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Translation\Helper\TransitionTranslationHelper;

class ProcessButtonsStaticTranslations
{
    /** @var Transition[] */
    protected $processedTransitions = [];

    /** @var TransitionTranslationHelper */
    protected $translationHelper;

    /**
     * @param TransitionTranslationHelper $translationHelper
     */
    public function __construct(TransitionTranslationHelper $translationHelper)
    {
        $this->translationHelper = $translationHelper;
    }

    /**
     * @param OnButtonsMatched $event
     */
    public function processButtons(OnButtonsMatched $event)
    {
        foreach ($event->getButtons() as $button) {
            if ($button instanceof AbstractTransitionButton) {
                $this->processTransition($button->getTransition());
            }
        }
    }

    /**
     * @param Transition $transition
     */
    protected function processTransition(Transition $transition)
    {
        if (in_array($transition, $this->processedTransitions, true)) {
            return;
        }
        $this->processedTransitions[] = $transition;

        $this->translationHelper->processTransitionTranslations($transition);
    }
}
