<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class FileConfigType extends AbstractType
{
    const NAME = 'oro_attachment_file_config';

    /** @var ConfigManager */
    protected $configManager;

    /** @var Config */
    protected $config;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function () use ($options) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $options['config_id'];

                $entityConfig = $this->configManager
                    ->getProvider('extend')
                    ->getConfig($fieldConfigId->getClassName());
                if ($entityConfig->is('state', ExtendScope::STATE_ACTIVE)
                    && !$this->hasRelation($entityConfig, $this->getRelationKey($fieldConfigId))
                ) {
                    $entityConfig->set('state', ExtendScope::STATE_UPDATE);
                    $this->configManager->persist($entityConfig);
                    $this->configManager->flush();
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'mapped' => false,
                'label'  => false
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param FieldConfigId $fieldConfigId
     *
     * @return string
     */
    protected function getRelationKey(FieldConfigId $fieldConfigId)
    {
        return ExtendHelper::buildRelationKey(
            $fieldConfigId->getClassName(),
            $fieldConfigId->getFieldName(),
            'manyToOne',
            'Oro\Bundle\AttachmentBundle\Entity\File'
        );
    }

    /**
     * @param ConfigInterface $entityConfig
     * @param string          $relationKey
     *
     * @return bool
     */
    protected function hasRelation(ConfigInterface $entityConfig, $relationKey)
    {
        $relations = $entityConfig->get('relation', false, []);

        return isset($relations[$relationKey]);
    }
}
