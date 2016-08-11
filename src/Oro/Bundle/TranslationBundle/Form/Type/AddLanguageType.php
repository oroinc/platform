<?php

namespace Oro\Bundle\TranslationBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Intl\Intl;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\TranslationBundle\Entity\Language;

class AddLanguageType extends AbstractType
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices'     => array_flip($this->getLanguageChoices()),
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
        $data = $this->managerRegistry
            ->getManagerForClass(Language::class)
            ->getRepository(Language::class)
            ->createQueryBuilder('l')
            ->select('l.code')
            ->getQuery()
            ->getArrayResult();

        return array_diff_key(
            $allLanguages,
            array_flip(array_column($data, 'code'))
        );
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
