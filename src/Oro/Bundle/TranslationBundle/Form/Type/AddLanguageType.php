<?php

namespace Oro\Bundle\TranslationBundle\Form\Type;

use Symfony\Component\Intl\Intl;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;

class AddLanguageType extends AbstractType
{
    /** @var LanguageRepository */
    protected $languageRepository;

    /**
     * @param LanguageRepository $languageRepository
     */
    public function __construct(LanguageRepository $languageRepository)
    {
        $this->languageRepository = $languageRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices'     => $this->getLanguageChoices(),
                'empty_value' => 'Please select...',
            ]
        );
    }

    /**
     * @return array
     */
    protected function getLanguageChoices()
    {
        $allLanguages = Intl::getLocaleBundle()->getLocaleNames('en');
        $codes = $this->languageRepository->getAvailableLanguageCodes();

        return array_diff(array_flip($allLanguages), $codes);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'locale';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_translation_add_language';
    }
}
