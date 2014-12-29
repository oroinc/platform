<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class FileConfigType extends AbstractType
{
    const NAME = 'oro_attachment_file_config';

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var Config */
    protected $config;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->extendConfigProvider = $configManager->getProvider('extend');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function () use ($options) {
                /** @var FieldConfigId $configId */
                $configId = $options['config_id'];

                $relationKey = ExtendHelper::buildRelationKey(
                    $configId->getClassName(),
                    $configId->getFieldName(),
                    'manyToOne',
                    'Oro\Bundle\AttachmentBundle\Entity\File'
                );

                /** @var Config $entityExtendConfig */
                $entityExtendConfig = $this->extendConfigProvider->getConfig($configId->getClassName());
                if ($this->isApplicable($entityExtendConfig, $relationKey)) {
                    if ($entityExtendConfig->is('state', ExtendScope::STATE_ACTIVE)) {
                        $entityExtendConfig->set('state', ExtendScope::STATE_UPDATE);

                        $this->extendConfigProvider->persist($entityExtendConfig);
                        $this->extendConfigProvider->flush();
                    }
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
     * @param  Config $entityConfig
     * @param  string $relationKey
     * @return bool
     */
    protected function isApplicable($entityConfig, $relationKey)
    {
        return
            $entityConfig->has('relation')
            && (
                !isset($entityConfig->get('relation')[$relationKey])
                || $entityConfig->get('relation')[$relationKey]['assign'] === false
            );
    }
}
