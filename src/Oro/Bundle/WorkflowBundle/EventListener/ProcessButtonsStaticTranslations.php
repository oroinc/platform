<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\ActionBundle\Provider\Event\OnButtonsMatched;
use Oro\Bundle\WorkflowBundle\Button\AbstractTransitionButton;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Translation\Helper\TransitionTranslationHelper;

/**
 * Processes static translations for workflow transition buttons.
 *
 * This listener responds to button matching events to ensure that transition button labels
 * and other translatable content are properly registered for static translation extraction.
 */
class ProcessButtonsStaticTranslations
{
    /** @var Transition[] */
    protected $processedTransitions = [];

    /** @var TransitionTranslationHelper */
    protected $translationHelper;

    public function __construct(TransitionTranslationHelper $translationHelper)
    {
        $this->translationHelper = $translationHelper;
    }

    public function processButtons(OnButtonsMatched $event)
    {
        foreach ($event->getButtons() as $button) {
            if ($button instanceof AbstractTransitionButton) {
                $this->processTransition($button->getTransition());
            }
        }
    }

    protected function processTransition(Transition $transition)
    {
        if (in_array($transition, $this->processedTransitions, true)) {
            return;
        }
        $this->processedTransitions[] = $transition;

        $this->translationHelper->processTransitionTranslations($transition);
    }
}
