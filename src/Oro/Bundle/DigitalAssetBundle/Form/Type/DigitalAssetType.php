<?php

namespace Oro\Bundle\DigitalAssetBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form type for DigitalAsset
 * - used on create/update page
 */
class DigitalAssetType extends AbstractType
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'titles',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.digitalasset.titles.label',
                    'required' => true,
                    'entry_options' => ['constraints' => [new NotBlank()]],
                    'block' => 'general',
                ]
            );

        // Adds sourceFile with option checkEmptyFile=true if entity is new, checkEmptyFile=false otherwise.
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            static function (FormEvent $event) {
                $digitalAsset = $event->getData();

                $event->getForm()->add(
                    'sourceFile',
                    FileType::class,
                    [
                        'label' => 'oro.digitalasset.source_file.label',
                        'required' => true,
                        'allowDelete' => false,
                        'checkEmptyFile' => !$digitalAsset || null === $digitalAsset->getId(),
                        'addEventSubscriber' => false,
                        'block' => 'general',
                    ]
                );
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => DigitalAsset::class,
                'block_config' => [
                    'general' => [
                        'title' => $this->translator->trans('oro.digitalasset.controller.sections.general.label'),
                    ],
                ],
                'validation_groups' => ['Default', 'DigitalAsset'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_digital_asset';
    }
}
