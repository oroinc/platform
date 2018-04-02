<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageSelectType extends AbstractType
{
    const NAME = 'oro_language_select';

    /** @var LocalizationChoicesProvider */
    private $provider;

    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param LocalizationChoicesProvider $provider
     * @param ManagerRegistry $registry
     */
    public function __construct(LocalizationChoicesProvider $provider, ManagerRegistry $registry)
    {
        $this->provider = $provider;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(
            new EntityToIdTransformer(
                $this->registry->getManagerForClass(Language::class),
                Language::class
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'placeholder' => '',
            'choices' => $this->provider->getLanguageChoices(true),
            'translatable_options' => false,
            'configs' => [
                'placeholder' => 'oro.locale.localization.form.placeholder.select_language',
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroChoiceType::class;
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
        return static::NAME;
    }
}
