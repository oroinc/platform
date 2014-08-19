<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueEnumName;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class EnumNameType extends AbstractType
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DynamicTranslationMetadataCache */
    protected $dbTranslationMetadataCache;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /**
     * @param ManagerRegistry                 $doctrine
     * @param TranslatorInterface             $translator
     * @param DynamicTranslationMetadataCache $dbTranslationMetadataCache
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     */
    public function __construct(
        ManagerRegistry $doctrine,
        TranslatorInterface $translator,
        DynamicTranslationMetadataCache $dbTranslationMetadataCache,
        ExtendDbIdentifierNameGenerator $nameGenerator
    ) {
        $this->doctrine                   = $doctrine;
        $this->translator                 = $translator;
        $this->dbTranslationMetadataCache = $dbTranslationMetadataCache;
        $this->nameGenerator              = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        if ($form->isValid()) {
            // add translations for an entity will be used to store enum values
            $enumName          = $event->getData();
            $enumCode          = ExtendHelper::buildEnumCode($enumName);
            $labelsToBeUpdated = [
                ExtendHelper::getEnumTranslationKey('label', $enumCode)        => $enumName,
                ExtendHelper::getEnumTranslationKey('plural_label', $enumCode) => $enumName,
                ExtendHelper::getEnumTranslationKey('description', $enumCode)  => '',
            ];
            /** @var EntityManager $em */
            $em = $this->doctrine->getManagerForClass(Translation::ENTITY_NAME);
            /** @var TranslationRepository $translationRepo */
            $translationRepo = $em->getRepository(Translation::ENTITY_NAME);
            $locale          = $this->translator->getLocale();
            $transValues     = [];
            foreach ($labelsToBeUpdated as $labelKey => $labelText) {
                // save into translation table
                $transValues[] = $translationRepo->saveValue(
                    $labelKey,
                    $labelText,
                    $locale,
                    TranslationRepository::DEFAULT_DOMAIN,
                    Translation::SCOPE_UI
                );
            }
            // mark translation cache dirty
            $this->dbTranslationMetadataCache->updateTimestamp($locale);
            // flush translations to db
            $em->flush($transValues);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => $this->nameGenerator->getMaxEnumCodeSize()]),
                    new Regex(
                        [
                            'pattern' => '/^[\w- ]*$/',
                            'message' => 'This value should contains only alphabetic symbols,'
                                . ' numbers, spaces, underscore or minus symbols'
                        ]
                    )
                ]
            )
        );

        $constraintsNormalizer = function (Options $options, $constraints) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $options['config_id'];

            $constraints[] = new UniqueEnumName(
                [
                    'entityClassName' => $fieldConfigId->getClassName(),
                    'fieldName'       => $fieldConfigId->getFieldName()
                ]
            );

            return $constraints;
        };

        $resolver->setNormalizers(
            array(
                'constraints' => $constraintsNormalizer
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_extend_enum_name';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'text';
    }
}
