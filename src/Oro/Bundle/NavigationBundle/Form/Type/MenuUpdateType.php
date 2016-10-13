<?php

namespace Oro\Bundle\NavigationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Form\DataTransformer\MenuUpdateTransformer;

class MenuUpdateType extends AbstractType
{
    const NAME = 'oro_navigation_menu_update';

    /** @var TranslatorInterface */
    private $translator;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /**
     * @param TranslatorInterface $translator
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(TranslatorInterface $translator, LocalizationHelper $localizationHelper)
    {
        $this->translator = $translator;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'titles',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'required' => true,
                    'label' => 'oro.navigation.menuupdate.title.label',
                    'options' => ['constraints' => [new NotBlank()]]
                ]
            )
            ->add(
                'uri',
                'text',
                [
                    'required' => true,
                    'label' => 'oro.navigation.menuupdate.uri.label',
                ]
            )
            ->add(
                'active',
                'checkbox',
                [
                    'label' => 'oro.navigation.menuupdate.active.label',
                ]
            )
        ;

        $builder->addViewTransformer(
            new MenuUpdateTransformer($this->translator, $this->localizationHelper)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MenuUpdate::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
