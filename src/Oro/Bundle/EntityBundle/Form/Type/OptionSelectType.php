<?php

namespace Oro\Bundle\EntityBundle\Form\Type;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\EntityConfigBundle\Entity\OptionSet;
use Oro\Bundle\EntityConfigBundle\Entity\OptionSetRelation;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\OptionSetRelationRepository;

class OptionSelectType extends AbstractType
{
    const NAME   = 'oro_option_select';
    const PARENT = 'choice';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var OroEntityManager
     */
    protected $em;

    /**
     * @var EntityRepository
     */
    protected $options;

    /**
     * @var OptionSetRelationRepository
     */
    protected $relations;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;

        $this->em        = $this->configManager->getEntityManager();
        $this->options   = $this->em->getRepository(OptionSet::ENTITY_NAME);
        $this->relations = $this->em->getRepository(OptionSetRelation::ENTITY_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'preSubmitData'));
    }

    /**
     * PRE_SET_DATA event handler
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $configFieldModel = $this->configManager->getConfigFieldModel(
            $event->getForm()->getConfig()->getOption('entityClassName'),
            $event->getForm()->getConfig()->getOption('entityFieldName')
        );

        $entityId = $this->getEntityId($event);
        if ($entityId) {
            $this->setData($event, $this->getSavedOptionIds($configFieldModel, $entityId));
        } else {
            $this->setData($event, $this->getDefaultOptionIds($configFieldModel));
        }
    }

    /**
     * PRE_SUBMIT event handler
     *
     * @param FormEvent $event
     */
    public function preSubmitData(FormEvent $event)
    {
        $entityId = $this->getEntityId($event);
        if ($entityId) {
            $configFieldModel = $this->configManager->getConfigFieldModel(
                $event->getForm()->getConfig()->getOption('entityClassName'),
                $event->getForm()->getConfig()->getOption('entityFieldName')
            );
            $savedOptionIds   = $this->getSavedOptionIds($configFieldModel, $entityId);

            $data = $event->getData();
            if (empty($data)) {
                $data = [];
            }
            if (!is_array($data)) {
                $data = [$data];
            }

            /**
             * Save selected options
             */
            $toSave = array_intersect($data, $savedOptionIds);
            foreach ($data as $option) {
                if (!in_array($option, $savedOptionIds)) {
                    $optionRelation = new OptionSetRelation();
                    $optionRelation->setData(null, $entityId, $configFieldModel, $this->options->find($option));
                    $toSave[] = $option;

                    $this->em->persist($optionRelation);
                }
            }

            /**
             * Remove unselected
             */
            if ($entityId && $this->relations->count($configFieldModel->getId(), $entityId)) {
                $toRemove = $this->relations->findByNotIn($configFieldModel->getId(), $entityId, $toSave);
                foreach ($toRemove as $option) {
                    $this->em->remove($option);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $that    = $this;
        $choices = function (Options $options) use ($that) {
            return $that->getChoices($options['entityClassName'], $options['entityFieldName']);
        };
        $resolver->setDefaults(
            [
                'choices'     => $choices,
                'empty_value' => ''
            ]
        );
        $resolver->setRequired(['entityClassName', 'entityFieldName']);

        $multipleNormalizer   = function (Options $options, $value) use ($that) {
            $extendConfig = $that->getExtendProvider()
                ->getConfig($options['entityClassName'], $options['entityFieldName']);

            return $extendConfig->is('set_expanded');
        };
        $emptyValueNormalizer = function (Options $options, $value) use ($that) {
            $extendConfig = $that->getExtendProvider()
                ->getConfig($options['entityClassName'], $options['entityFieldName']);
            if (!$extendConfig->is('set_expanded')) {
                $value = 'oro.form.choose_value';
            }

            return $value;
        };
        $resolver->setNormalizers(
            array(
                'multiple'    => $multipleNormalizer,
                'empty_value' => $emptyValueNormalizer
            )
        );
    }

    /**
     * Returns id of an entity associated with root form
     *
     * @param FormEvent $event
     * @return int|null
     */
    protected function getEntityId(FormEvent $event)
    {
        $entityId = null;
        $formData = $event->getForm()->getRoot()->getData();
        if ($formData && method_exists($formData, 'getId')) {
            $entityId = $formData->getId();
        }

        return $entityId;
    }

    /**
     * Sets form data
     *
     * @param FormEvent $event
     * @param mixed     $data
     */
    protected function setData(FormEvent $event, $data)
    {
        if ($event->getForm()->getConfig()->getOption('multiple')) {
            $event->setData($data ? $data : []);
        } else {
            $event->setData($data ? array_shift($data) : '');
        }
    }

    /**
     * Returns a list of all choices for an option set
     *
     * @param string $entityClassName
     * @param string $entityFieldName
     * @return array
     */
    protected function getChoices($entityClassName, $entityFieldName)
    {
        $configFieldModel = $this->configManager->getConfigFieldModel($entityClassName, $entityFieldName);
        $options          = $configFieldModel->getOptions()->toArray();
        uasort(
            $options,
            function ($a, $b) {
                if ($a->getPriority() === $b->getPriority()) {
                    return 0;
                }

                return ($a->getPriority() < $b->getPriority()) ? -1 : 1;
            }
        );
        $result = [];
        foreach ($options as $option) {
            $result[$option->getId()] = $option->getLabel();
        }

        return $result;
    }

    /**
     * @return ConfigProvider
     */
    protected function getExtendProvider()
    {
        return $this->configManager->getProvider('extend');
    }

    /**
     * Returns already set options for the given option set and entity
     *
     * @param FieldConfigModel $configFieldModel
     * @param int              $entityId
     * @return int[]
     */
    protected function getSavedOptionIds(FieldConfigModel $configFieldModel, $entityId)
    {
        $savedOptionIds = $this->relations->findByFieldId($configFieldModel->getId(), $entityId);
        array_walk(
            $savedOptionIds,
            function (&$item) {
                $item = $item->getOption()->getId();
            }
        );

        return $savedOptionIds;
    }

    /**
     * Returns default options for the given option set
     *
     * @param FieldConfigModel $configFieldModel
     * @return int[]
     */
    protected function getDefaultOptionIds(FieldConfigModel $configFieldModel)
    {
        $defaultOptionIds = [];
        foreach ($configFieldModel->getOptions()->toArray() as $option) {
            if ($option->getIsDefault()) {
                $defaultOptionIds[] = $option->getId();
            }
        }

        return $defaultOptionIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return self::PARENT;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
