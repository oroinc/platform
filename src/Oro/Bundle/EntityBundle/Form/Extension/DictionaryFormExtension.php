<?php

namespace Oro\Bundle\EntityBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * To be able to edit dictionary like entities, e.g.:
 * entities that has no id auto-generated column,
 * have 'dictionary' group and at least two fields:
 * name (identifier, could have any name) and label.
 */
class DictionaryFormExtension extends AbstractTypeExtension
{
    /** @var ConfigProviderInterface */
    protected $groupingConfigProvider;

    /**
     * @param ConfigProviderInterface $groupingConfigProvider
     */
    public function __construct(ConfigProviderInterface $groupingConfigProvider) {
        $this->groupingConfigProvider = $groupingConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'custom_entity_type';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (empty($options['data_class'])) {
            return;
        }

        $className = $options['data_class'];
        if (!$this->groupingConfigProvider->hasConfig($className)) {
            return;
        }

        /** @var ConfigInterface $config */
        $config = $this->groupingConfigProvider->getConfig($className);
        if (!in_array(GroupingScope::GROUP_DICTIONARY, $config->get('groups', false, []))) {
            return;
        }

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                /** @var object $entity */
                $entity = $event->getData();

                if (!$entity->getName() && $entity->getLabel()) {
                    $dataClass = $event->getForm()->getConfig()->getDataClass();

                    // set dictionary name identifier, if it's empty,
                    // re-create entity as it has no setName method

                    $name = ExtendHelper::buildEnumCode($entity->getLabel());
                    // max dictionary identifier length, encode long ids automatically
                    if (strlen($name) > 16) {
                        $name = substr($name, 0, 8) . dechex(crc32($name));
                    }

                    /** @var object $newEntity */
                    $newEntity = new $dataClass($name);
                    $newEntity->setLabel($entity->getLabel());
                    $newEntity->setOrder($entity->getOrder());
                    unset($entity);

                    $event->setData($newEntity);
                }
            }
        );
    }
}
