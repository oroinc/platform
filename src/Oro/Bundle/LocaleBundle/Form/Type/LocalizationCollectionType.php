<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Model\FallbackType;

class LocalizationCollectionType extends AbstractType
{
    const NAME = 'oro_locale_localization_collection';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @return Localization[]
     */
    protected $localizations;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'type',
        ]);

        $resolver->setDefaults([
            'options'               => [],
            'fallback_type'         => FallbackPropertyType::NAME,
            'enabled_fallbacks'     => [],
            'value_type'            => FallbackValueType::NAME,
            'group_fallback_fields' => null
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->getLocalizations() as $localization) {
            // calculate enabled fallbacks for the specific localization
            $enabledFallbacks = $options['enabled_fallbacks'];
            $parent = null;
            if ($localization->getParentLocalization()) {
                $enabledFallbacks = array_merge($enabledFallbacks, [FallbackType::PARENT_LOCALIZATION]);
                $parent = $localization->getParentLocalization()->getName();
            }

            $builder->add(
                $localization->getId(),
                $options['value_type'],
                [
                    'label' => $localization->getName(),
                    'type' => $options['type'],
                    'options' => $options['options'],
                    'fallback_type' => $options['fallback_type'],
                    'fallback_type_localization' => $localization->getName(),
                    'fallback_type_parent_localization' => $parent,
                    'enabled_fallbacks' => $enabledFallbacks,
                    'group_fallback_fields' => $options['group_fallback_fields']
                ]
            );
        }

        $localizations = $this->getLocalizations();
        if ($localizations) {
            // use any localization field to resolve default data
            $localization = $localizations[0];
            $localizationField = $builder->get($localization->getId());

            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($localizationField) {
                $data = $event->getData();
                $filledData = $this->fillDefaultData($data, $localizationField);
                $event->setData($filledData);
            });
        }
    }

    /**
     * @return Localization[]
     */
    protected function getLocalizations()
    {
        if (null === $this->localizations) {
            /** @var EntityRepository $entityRepository */
            $entityRepository = $this->registry->getRepository($this->dataClass);

            $this->localizations = $entityRepository->createQueryBuilder('l')
                ->leftJoin('l.parentLocalization', 'parent')
                ->addOrderBy('l.id', 'ASC')
                ->getQuery()
                ->getResult();
        }

        return $this->localizations;
    }

    /**
     * @param mixed $data
     * @param FormBuilderInterface $form
     * @return array
     */
    public function fillDefaultData($data, FormBuilderInterface $form)
    {
        if (!$data) {
            $data = [];
        }

        foreach ($this->getLocalizations() as $localization) {
            $localizationId = $localization->getId();
            if (!array_key_exists($localizationId, $data)) {
                if ($localization->getParentLocalization()) {
                    $data[$localizationId] = new FallbackType(FallbackType::PARENT_LOCALIZATION);
                } else {
                    $data[$localizationId] = new FallbackType(FallbackType::SYSTEM);
                }
                if ($form->hasOption('default_callback')) {
                    /** @var \Closure $defaultCallback */
                    $defaultCallback = $form->getOption('default_callback');
                    $data[$localizationId] = $defaultCallback($data[$localizationId]);
                }
            }
        }

        return $data;
    }
}
