<?php

namespace Oro\Bundle\WorkflowBundle\Translation\Helper;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;

class TransitionTranslationHelper
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param Transition $transition
     */
    public function processTransitionTranslations(Transition $transition)
    {
        $buttonLabel = $this->trans($transition->getButtonLabel());
        if (null !== $buttonLabel) {
            $transition->setButtonLabel($buttonLabel);
        } else {
            $transition->setButtonLabel($this->trans($transition->getLabel()));
        }

        $buttonTitle = $this->trans($transition->getButtonTitle());
        if (null !== $buttonTitle) {
            $transition->setButtonTitle($buttonTitle);
        } else {
            $transition->setButtonTitle($transition->getButtonLabel());
        }

        $frontendOptions = $transition->getFrontendOptions();
        if (empty($frontendOptions['message']['title'])) {
            $frontendOptions['message']['title'] = $transition->getButtonTitle();
        }
        $transition->setFrontendOptions($frontendOptions);
    }

    /**
     * @param string $id
     * @param array $parameters
     * @return string|null
     */
    protected function trans($id, array $parameters = [])
    {
        $translate = $this->translator->trans($id, $parameters, WorkflowTranslationHelper::TRANSLATION_DOMAIN);

        return !$translate || $translate === $id ? null : $translate;
    }
}
