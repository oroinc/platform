<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use Oro\Bundle\LocaleBundle\Model\FallbackType;

class LocaleCollectionType extends AbstractType
{
    const NAME = 'oro_locale_locale_collection';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @return Locale[]
     */
    protected $locales;

    /**
     * @var string
     */
    protected $localeClass;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $localeClass
     */
    public function setLocaleClass($localeClass)
    {
        $this->localeClass = $localeClass;
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
        foreach ($this->getLocales() as $locale) {
            // calculate enabled fallbacks for the specific locale
            $enabledFallbacks = $options['enabled_fallbacks'];
            $parentLocaleCode = null;
            if ($locale->getParentLocale()) {
                $enabledFallbacks = array_merge($enabledFallbacks, [FallbackType::PARENT_LOCALE]);
                $parentLocaleCode = $locale->getParentLocale()->getCode();
            }

            $builder->add(
                $locale->getId(),
                $options['value_type'],
                [
                    'label'                       => $locale->getCode(),
                    'type'                        => $options['type'],
                    'options'                     => $options['options'],
                    'fallback_type'               => $options['fallback_type'],
                    'fallback_type_locale'        => $locale->getCode(),
                    'fallback_type_parent_locale' => $parentLocaleCode,
                    'enabled_fallbacks'           => $enabledFallbacks,
                    'group_fallback_fields'       => $options['group_fallback_fields']
                ]
            );
        }

        $locales = $this->getLocales();
        if ($locales) {
            // use any locale field to resolve default data
            $locale = $locales[0];
            $localeField = $builder->get($locale->getId());

            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($localeField) {
                $data = $event->getData();
                $filledData = $this->fillDefaultData($data, $localeField);
                $event->setData($filledData);
            });
        }
    }

    /**
     * @return Locale[]
     */
    protected function getLocales()
    {
        if (null === $this->locales) {
            /** @var EntityRepository $entityRepository */
            $entityRepository = $this->registry->getRepository($this->localeClass);

            $this->locales = $entityRepository->createQueryBuilder('locale')
                ->leftJoin('locale.parentLocale', 'parentLocale')
                ->addOrderBy('locale.id', 'ASC')
                ->getQuery()
                ->getResult();
        }

        return $this->locales;
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

        foreach ($this->getLocales() as $locale) {
            $localeId = $locale->getId();
            if (!array_key_exists($localeId, $data)) {
                if ($locale->getParentLocale()) {
                    $data[$localeId] = new FallbackType(FallbackType::PARENT_LOCALE);
                } else {
                    $data[$localeId] = new FallbackType(FallbackType::SYSTEM);
                }
                if ($form->hasOption('default_callback')) {
                    /** @var \Closure $defaultCallback */
                    $defaultCallback = $form->getOption('default_callback');
                    $data[$localeId] = $defaultCallback($data[$localeId]);
                }
            }
        }

        return $data;
    }
}
