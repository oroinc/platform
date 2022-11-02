<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Form\Util\DynamicFieldsHelper;
use Oro\Component\PhpUtils\ArrayUtil;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds extended fields to a form based on the configuration for an entity/field.
 */
class DynamicFieldsExtension extends AbstractTypeExtension implements ServiceSubscriberInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ContainerInterface */
    protected $container;

    /** @var LoggerInterface */
    private $logger;

    /** @var TranslatorInterface */
    private $translator;

    /** @var bool */
    private $debug;

    public function __construct(
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        ContainerInterface $container,
        bool $debug
    ) {
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->container = $container;
        $this->debug = $debug;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_entity_extend.form.extension.dynamic_fields_helper' => DynamicFieldsHelper::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->isApplicable($options)) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData'], -255);
        }
    }

    public function preSetData(PreSetDataEvent $event): void
    {
        $form = $event->getForm();
        $className = $form->getConfig()->getOption('data_class');

        $extendConfigProvider = $this->configManager->getProvider('extend');
        $viewConfigProvider   = $this->configManager->getProvider('view');
        $attributeConfigProvider  = $this->configManager->getProvider('attribute');

        $fields      = [];
        $formConfigs = $this->configManager->getProvider('form')->getConfigs($className);
        foreach ($formConfigs as $formConfig) {
            if (!$formConfig->is('is_enabled')) {
                continue;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $formConfig->getId();
            $fieldName     = $fieldConfigId->getFieldName();

            $attributeConfig = $attributeConfigProvider->getConfig($className, $fieldName);
            if ($attributeConfig->is('is_attribute')) {
                continue;
            }

            $extendConfig  = $extendConfigProvider->getConfig($className, $fieldName);
            if (!$this->isApplicableField($extendConfig, $extendConfigProvider)) {
                continue;
            }

            $fields[$fieldName] = [
                'priority' => $viewConfigProvider->getConfig($className, $fieldName)->get('priority', false, 0)
            ];
        }

        ArrayUtil::sortBy($fields, true);
        foreach ($fields as $fieldName => $priority) {
            if (!$this->fieldExists($form, $fieldName)) {
                $form->add($fieldName, null, ['is_dynamic_field' => true]);
            }
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!$this->isApplicable($options)) {
            return;
        }

        $className = $options['data_class'];

        $extendConfigProvider = $this->configManager->getProvider('extend');
        $attributeConfigProvider  = $this->configManager->getProvider('attribute');

        $formConfigs = $this->configManager->getProvider('form')->getConfigs($className);
        foreach ($formConfigs as $formConfig) {
            if (!$formConfig->is('is_enabled')) {
                continue;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $formConfig->getId();
            $fieldName     = $fieldConfigId->getFieldName();

            $attributeConfig = $attributeConfigProvider->getConfig($className, $fieldName);
            if ($attributeConfig->is('is_attribute')) {
                continue;
            }

            $extraField = false;
            if (array_key_exists($fieldName, $view->children)) {
                $extraField = $this->isDynamicField($form, $fieldName);
            }

            if (!$this->getDynamicFieldsHelper()->shouldBeInitialized($className, $formConfig, $view, $extraField)) {
                continue;
            }

            $extendConfig = $extendConfigProvider->getConfig($className, $fieldName);

            $this->getDynamicFieldsHelper()->addInitialElements($view, $form, $extendConfig);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'dynamic_fields_disabled' => false,
            ]
        );
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    protected function isApplicable(array $options)
    {
        if ($options['dynamic_fields_disabled'] || empty($options['data_class']) || empty($options['compound'])) {
            return false;
        }

        $className = $options['data_class'];
        if (!$this->doctrineHelper->isManageableEntity($className)) {
            return false;
        }
        if (!$this->configManager->getProvider('extend')->hasConfig($className)) {
            return false;
        }

        return true;
    }

    /**
     * @param ConfigInterface $extendConfig
     * @param ConfigProvider  $extendConfigProvider
     *
     * @return bool
     */
    protected function isApplicableField(ConfigInterface $extendConfig, ConfigProvider $extendConfigProvider)
    {
        return $this->getDynamicFieldsHelper()->isApplicableField($extendConfig, $extendConfigProvider);
    }

    protected function getDynamicFieldsHelper(): DynamicFieldsHelper
    {
        return $this->container->get('oro_entity_extend.form.extension.dynamic_fields_helper');
    }

    private function fieldExists(FormInterface $form, string $fieldName): bool
    {
        if ($form->has($fieldName)) {
            if ($this->isDynamicField($form, $fieldName)) {
                return true;
            }

            if (!$this->isIgnoreException($form, $fieldName)) {
                $this->createException($fieldName);
            }

            return true;
        }

        return false;
    }

    private function isDynamicField(FormInterface $form, string $fieldName): bool
    {
        return $form->get($fieldName)->getConfig()->getOption('is_dynamic_field', false);
    }

    private function isIgnoreException(FormInterface $form, string $fieldName): bool
    {
        return $form->get($fieldName)->getConfig()->getOption('dynamic_fields_ignore_exception', false);
    }

    private function createException(string $fieldName): void
    {
        $message = $this->translator->trans('oro.entity_extend.form.field_exists', ['%fieldName%' => $fieldName]);
        if ($this->debug) {
            throw new \LogicException($message);
        } else {
            $this->logger->critical($message);
        }
    }

    /**
     * Does not return any values ​​since the extension has a specific using.
     */
    public static function getExtendedTypes(): array
    {
        return [FormType::class];
    }
}
