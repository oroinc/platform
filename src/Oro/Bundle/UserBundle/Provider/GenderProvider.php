<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\UserBundle\Model\Gender;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides available genders.
 */
class GenderProvider
{
    private TranslatorInterface $translator;
    private ?array $translatedChoices = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return array [gender name => gender code, ...]
     */
    public function getChoices(): array
    {
        if (null === $this->translatedChoices) {
            $this->translatedChoices = [];
            $this->translatedChoices[$this->translator->trans('oro.user.gender.male')] = Gender::MALE;
            $this->translatedChoices[$this->translator->trans('oro.user.gender.female')] = Gender::FEMALE;
        }

        return $this->translatedChoices;
    }

    public function getLabelByName(string $name): string
    {
        $choices = $this->getChoices();
        $label = array_search($name, $choices, true);
        if (false === $label) {
            throw new \LogicException(sprintf('Unknown gender with name "%s"', $name));
        }

        return $label;
    }
}
