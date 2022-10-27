<?php

namespace Oro\Bundle\ImportExportBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Type\AbstractConfigType;
use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Disable changing of an attribute value and set default value
 */
class IdentityConfigChoiceType extends AbstractConfigType
{
    const CHOICE_NO = 0;
    const CHOICE_WHEN_NOT_EMPTY = -1;
    const CHOICE_ALWAYS = 1;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ConfigTypeHelper $typeHelper, ManagerRegistry $registry)
    {
        parent::__construct($typeHelper);
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            [$this, 'onPreSetData']
        );
    }

    /**
     * Set default value
     */
    public function onPreSetData(FormEvent $event)
    {
        $options = $event->getForm()->getConfig()->getOptions();
        if ($this->isImmutable($options)) {
            $event->setData(self::CHOICE_ALWAYS);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'choices' => [
                    'oro.importexport.entity_config.identity.no' => self::CHOICE_NO,
                    'oro.importexport.entity_config.identity.only_when_not_empty' => self::CHOICE_WHEN_NOT_EMPTY,
                    'oro.importexport.entity_config.identity.always' => self::CHOICE_ALWAYS
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
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
        return 'oro_importexport_identity_config_choice';
    }

    /**
     * {@inheritdoc}
     */
    protected function isReadOnly(Options $options)
    {
        return $this->isImmutable($options) || parent::isReadOnly($options);
    }

    /**
     * Checks if a config for the given field should be immutable
     *
     * @param Options|array $options
     *
     * @return bool
     */
    private function isImmutable($options)
    {
        /** @var FieldConfigId $configId */
        $configId = $options['config_id'];
        try {
            $identifiers = $this->registry->getManager()
                ->getClassMetadata($configId->getClassName())
                ->getIdentifierFieldNames();
        } catch (MappingException $mappingException) {
            return false;
        }

        return $configId->getScope() === 'importexport'
            && in_array($configId->getFieldName(), $identifiers, true);
    }
}
