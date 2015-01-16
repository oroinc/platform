<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * A base class for an enum value selector form types
 */
abstract class AbstractEnumType extends AbstractType
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ConfigManager   $configManager
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        ConfigManager $configManager,
        ManagerRegistry $doctrine
    ) {
        $this->configManager = $configManager;
        $this->doctrine      = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                // either enum_code or class must be specified
                'enum_code'     => null,
                'class'         => null,
                'query_builder' => function (EntityRepository $repo) {
                    return $repo->createQueryBuilder('o')->orderBy('o.priority');
                },
                'property'      => 'name',
                'multiple'      => null
            ]
        );

        $resolver->setNormalizers(
            [
                'class'    => function (Options $options, $value) {
                    return !empty($value)
                        ? $value
                        : ExtendHelper::buildEnumValueClassName($options['enum_code']);
                },
                'multiple' => function (Options $options, $value) {
                    return $value !== null
                        ? $value
                        : $this->configManager->getProvider('enum')->getConfig($options['class'])->is('multiple');
                }
            ]
        );
    }

    /**
     * PRE_SET_DATA event handler
     *
     * Sets default value for new entity in form in case if value is not set.
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $formConfig = $form->getConfig();

        $targetEntity = $this->getNewEntityFromNearestParentForm($form);

        if (!$targetEntity) {
            return null;
        }

        // Check to see if there's a value provided by the form.
        $accessor = PropertyAccess::createPropertyAccessor();
        try {
            if (null !== $accessor->getValue($targetEntity, $form->getPropertyPath())) {
                return;
            }
        } catch (NoSuchPropertyException $exception) {
            // If value cannot be get then treat it as value is empty and we need to suppress this exception.
        }

        // Set initial options for new entity
        /** @var EntityRepository $repo */
        $repo = $this->doctrine->getRepository($formConfig->getOption('class'));
        $data = $repo->createQueryBuilder('e')
            ->where('e.default = true')
            ->getQuery()
            ->getResult();
        if ($formConfig->getOption('multiple')) {
            $event->setData($data ? $data : []);
        } else {
            $event->setData($data ? array_shift($data) : '');
        }
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
