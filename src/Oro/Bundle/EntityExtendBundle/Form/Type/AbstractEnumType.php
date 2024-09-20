<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * A base class for an enum value selector form types
 */
abstract class AbstractEnumType extends AbstractType
{
    public function __construct(protected ConfigManager $configManager, protected ManagerRegistry $doctrine)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                // either enum_code or class must be specified
                'enum_code' => null,
                'class' => null,
                'choice_label' => 'name',
                'multiple' => null,
                'query_builder' => function (EnumOptionRepository $repo) {
                },
            ]
        );

        $resolver->setNormalizer(
            'query_builder',
            function (Options $options) {
                return function (EnumOptionRepository $repo) use ($options) {
                    return $repo->getValuesQueryBuilder($options['enum_code']);
                };
            }
        );

        $resolver->setNormalizer(
            'class',
            function (Options $options, $value) {
                if ($value !== null) {
                    return $value;
                }

                if (empty($options['enum_code'])) {
                    throw new InvalidOptionsException('Either "class" or "enum_code" must option must be set.');
                }

                return EnumOption::class;
            }
        );
        $resolver->setNormalizer(
            'multiple',
            function (Options $options, $value) {
                if ($value === null && !empty($options['class'])) {
                    $value = $this->configManager
                        ->getProvider('enum')
                        ->getConfig($options['class'])
                        ->is('multiple');
                }

                return $value;
            }
        );
    }

    /**
     * PRE_SET_DATA event handler
     *
     * Sets default value for new entity in form in case if value is not set.
     *
     * @param FormEvent $event
     * @return null|void
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $formConfig = $form->getConfig();

        $targetEntity = $this->getNewEntityFromNearestParentForm($form);

        if (!$targetEntity) {
            return null;
        }

        if (!$this->isDataEmptyValue($targetEntity, $form)) {
            return;
        }

        // Set initial options for new entity
        /** @var EnumOptionRepository $repo */
        $repo = $this->doctrine->getRepository($formConfig->getOption('class'));
        $data = $repo->getDefaultValues($formConfig->getOption('enum_code'));
        if ($formConfig->getOption('multiple')) {
            $event->setData($data ?: []);
        } else {
            $event->setData($data ? array_shift($data) : '');
        }
    }

    /**
     * @param mixed $targetEntity
     * @param FormInterface $form
     * @return bool
     */
    protected function isDataEmptyValue($targetEntity, FormInterface $form)
    {
        $formConfig = $form->getConfig();

        // Check to see if there's a value provided by the form.
        $accessor = PropertyAccess::createPropertyAccessor();
        try {
            $value = $accessor->getValue($targetEntity, $form->getPropertyPath());
            if ($formConfig->getOption('multiple')) {
                $result = ($value instanceof Collection && $value->isEmpty()) || (is_array($value) && empty($value));
            } else {
                $result = (null === $value);
            }
        } catch (NoSuchPropertyException $exception) {
            // If value cannot be get then treat it as value as empty and we need to suppress this exception.
            $result = false;
        }

        return $result;
    }

    /**
     * @param FormInterface $form
     * @return mixed|null
     */
    protected function getNewEntityFromNearestParentForm(FormInterface $form)
    {
        $parent = $form->getParent();

        if (!$parent) {
            return null;
        }

        if ($parent->getConfig()->getOption('data_class')) {
            $data = $parent->getData();
            if ($data && is_object($data) && method_exists($data, 'getId') && $data->getId() === null) {
                return $data;
            }
            return null;
        }

        return $this->getNewEntityFromNearestParentForm($parent);
    }
}
