<?php

namespace Oro\Bundle\WorkflowBundle\Translation\Helper;

use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Symfony\Component\Translation\TranslatorInterface;

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
            $transition->setButtonLabel($this->trans($transition->getLabel(), true));
        }

        $buttonTitle = $this->trans($transition->getButtonTitle());
        if (null !== $buttonTitle) {
            $transition->setButtonTitle($buttonTitle);
        } else {
            $transition->setButtonTitle($transition->getButtonLabel());
        }

        $frontendOptions = $transition->getFrontendOptions();
        if (empty($frontendOptions['message']['title'])) {
            $frontendOptions['message']['title'] = $transition->getButtonLabel();
        }
        $transition->setFrontendOptions($frontendOptions);
    }

    /**
     * @param string $id
     * @param bool $allowEmptyTranslate
     * @return string|null
     */
    protected function trans($id, $allowEmptyTranslate = false)
    {
        $translate = $this->translator->trans($id, [], WorkflowTranslationHelper::TRANSLATION_DOMAIN);

        return $allowEmptyTranslate || $translate && $translate !== $id ? $translate : null;
    }
}
