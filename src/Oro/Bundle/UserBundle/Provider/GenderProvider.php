<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\UserBundle\Model\Gender;
use Symfony\Component\Translation\TranslatorInterface;

class GenderProvider
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $choices = [
        'oro.user.gender.male' => Gender::MALE,
        'oro.user.gender.female' => Gender::FEMALE,
    ];

    /**
     * @var array
     */
    protected $translatedChoices;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public function getChoices()
    {
        if (null === $this->translatedChoices) {
            $this->translatedChoices = array();
            foreach ($this->choices as $label => $name) {
                $this->translatedChoices[$this->translator->trans($label)] = $name;
            }
        }

        return $this->translatedChoices;
    }

    /**
     * @param string $name
     * @return string
     * @throws \LogicException
     */
    public function getLabelByName($name)
    {
        $choices = $this->getChoices();
        $label = array_search($name, $choices, true);
        if ($label === false) {
            throw new \LogicException(sprintf('Unknown gender with name "%s"', $name));
        }

        return $label;
    }
}
