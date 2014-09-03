<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form     = $event->getForm();
        $formData = $form->getRoot()->getData();
        if ($formData && is_object($formData) && method_exists($formData, 'getId') && $formData->getId() === null) {
            // set initial options for new entity
            $formConfig = $form->getConfig();
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
    }
}
