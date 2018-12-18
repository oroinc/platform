<?php

namespace Oro\Bundle\ActionBundle\Datagrid\Provider;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonsCollection;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Button\OperationButton;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\DefaultOperationRequestHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Extension\Action\DatagridActionProviderInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Populates datagrid config with actions and context configs that attached to it
 */
class DatagridActionButtonProvider implements DatagridActionProviderInterface
{
    /** @var ButtonProvider */
    protected $buttonProvider;

    /** @var ContextHelper */
    protected $contextHelper;

    /** @var MassActionProviderRegistry */
    protected $providerRegistry;

    /** @var OptionsHelper */
    protected $optionsHelper;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ButtonsCollection[] */
    protected $buttons = [];

    /** @var array */
    protected $groups;

    /**
     * @param ButtonProvider $buttonProvider
     * @param ContextHelper $contextHelper
     * @param MassActionProviderRegistry $providerRegistry
     * @param OptionsHelper $optionsHelper
     * @param EntityClassResolver $entityClassResolver
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ButtonProvider $buttonProvider,
        ContextHelper $contextHelper,
        MassActionProviderRegistry $providerRegistry,
        OptionsHelper $optionsHelper,
        EntityClassResolver $entityClassResolver,
        TranslatorInterface $translator
    ) {
        $this->buttonProvider = $buttonProvider;
        $this->contextHelper = $contextHelper;
        $this->providerRegistry = $providerRegistry;
        $this->optionsHelper = $optionsHelper;
        $this->entityClassResolver = $entityClassResolver;
        $this->translator = $translator;
    }

    /**
     * @param array $groups
     */
    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }

    /** {@inheritdoc} */
    public function hasActions(DatagridConfiguration $configuration)
    {
        return 0 !== count($this->getButtons($this->getButtonSearchContext($configuration), $configuration));
    }

    /**
     * @param DatagridConfiguration $configuration
     */
    public function applyActions(DatagridConfiguration $configuration)
    {
        if (!$this->hasActions($configuration)) {
            return;
        }

        $searchContext = $this->getButtonSearchContext($configuration);

        $this->applyContextConfig($configuration);
        $this->applyActionsConfig($configuration, $searchContext);

        $this->processMassActionsConfig($configuration, $searchContext);

        $configuration->offsetSet(
            ActionExtension::ACTION_CONFIGURATION_KEY,
            $this->getRowConfigurationClosure($configuration, $searchContext)
        );
    }

    /**
     * Returns buttons if they not already exist in datagrid config as actions
     *
     * @param ButtonSearchContext $searchContext
     * @param DatagridConfiguration $configuration
     * @return ButtonsCollection
     */
    protected function getButtons(ButtonSearchContext $searchContext, DatagridConfiguration $configuration)
    {
        $hash = $searchContext->getHash();
        if (!array_key_exists($hash, $this->buttons)) {
            $buttonCollection = $this->buttonProvider->match($searchContext);
            $datagridActionsConfig = $configuration->offsetGetOr(ActionExtension::ACTION_KEY, []);

            $this->buttons[$hash] = $buttonCollection->filter(
                function (ButtonInterface $button) use ($datagridActionsConfig) {
                    return !array_key_exists(strtolower($button->getName()), $datagridActionsConfig);
                }
            );
        }

        return $this->buttons[$hash];
    }

    /**
     * @param DatagridConfiguration $configuration
     * @param ButtonSearchContext $context
     * @return \Closure
     */
    protected function getRowConfigurationClosure(DatagridConfiguration $configuration, ButtonSearchContext $context)
    {
        $buttons = $this->getButtons($context, $configuration);
        $actionConfiguration = $configuration->offsetGetOr(ActionExtension::ACTION_CONFIGURATION_KEY, []);

        return function (ResultRecordInterface $record, array $config) use ($buttons, $actionConfiguration, $context) {
            $configuration = $this->retrieveConfiguration($actionConfiguration, $record, $config);

            $searchContext = clone $context;
            $searchContext->setEntity($searchContext->getEntityClass(), $record->getValue('id'));

            $buttons = $buttons->map(
                function (
                    ButtonInterface $button,
                    ButtonProviderExtensionInterface $extension
                ) use (
                    $configuration,
                    $searchContext
                ) {
                    $name = strtolower($button->getName());
                    $enabled = false;

                    if ($searchContext->getEntityId() === null) {
                        $configuration[$name] = false;
                    }

                    $newButton = clone $button;
                    if (!array_key_exists($name, $configuration) || $configuration[$name] !== false) {
                        $enabled = $extension->isAvailable($newButton, $searchContext);
                    }

                    $newButton->getButtonContext()
                        ->setEnabled($enabled)
                        ->setEntity($searchContext->getEntityClass(), $searchContext->getEntityId());

                    return $newButton;
                }
            );

            foreach ($buttons->getIterator() as $button) {
                $configuration[strtolower($button->getName())] = $this->getRowConfig($button);
            }

            return $configuration;
        };
    }

    /**
     * Retrieves parent action_configuration from callbacks
     * @param null|array|callable $actionConfiguration
     * @param ResultRecordInterface $record
     * @param array $config
     * @return array
     */
    protected function retrieveConfiguration($actionConfiguration, ResultRecordInterface $record, array $config)
    {
        if (empty($actionConfiguration)) {
            return [];
        }

        $rowActions = [];

        if (is_callable($actionConfiguration)) {
            $rowActions = $actionConfiguration($record, $config);
        } elseif (is_array($actionConfiguration)) {
            $rowActions = $actionConfiguration;
        }

        return is_array($rowActions) ? $rowActions : [];
    }

    /**
     * @param ButtonInterface $button
     * @return bool|array
     */
    protected function getRowConfig(ButtonInterface $button)
    {
        if (!$button->getButtonContext()->isEnabled()) {
            return false;
        }

        $frontendOptions = $this->optionsHelper->getFrontendOptions($button);

        return array_merge($frontendOptions['options'], $frontendOptions['data']);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function applyContextConfig(DatagridConfiguration $config)
    {
        $context = $this->contextHelper->getContext();

        if (!empty($context['route'])) {
            $config->offsetSetByPath(
                '[options][urlParams]['. DefaultOperationRequestHelper::ORIGINAL_ROUTE_URL_PARAMETER_KEY .']',
                $context['route']
            );
        }
    }

    /**
     * @param DatagridConfiguration $config
     * @param ButtonSearchContext $context
     */
    protected function applyActionsConfig(DatagridConfiguration $config, ButtonSearchContext $context)
    {
        $actionsConfig = $config->offsetGetOr(ActionExtension::ACTION_KEY, []);

        foreach ($this->getButtons($context, $config) as $button) {
            /** @var ButtonInterface $button */
            $name = strtolower($button->getName());

            $actionsConfig[$name] = $this->getRowsActionsConfig($button);
        }

        $config->offsetSet(ActionExtension::ACTION_KEY, $actionsConfig);
    }

    /**
     * @param ButtonInterface $button
     * @return array
     */
    protected function getRowsActionsConfig(ButtonInterface $button)
    {
        $icon = $button->getIcon() ? str_ireplace('fa-', '', $button->getIcon()) : 'pencil-square-o';

        $config = array_merge(
            [
                'type' => 'button-widget',
                'label' => $this->translator->trans($button->getLabel(), [], $button->getTranslationDomain()),
                'rowAction' => false,
                'link' => '#',
                'icon' => $icon,
            ],
            $button->getTemplateData()['additionalData']
        );

        if ($button->getOrder()) {
            $config['order'] = $button->getOrder();
        }

        return $config;
    }

    /**
     * @param DatagridConfiguration $config
     * @param ButtonSearchContext $context
     */
    protected function processMassActionsConfig(DatagridConfiguration $config, ButtonSearchContext $context)
    {
        $actions = $config->offsetGetOr('mass_actions', []);

        foreach ($this->getButtons($context, $config)->getIterator() as $button) {
            if (!$button instanceof OperationButton) {
                continue;
            }

            $datagridOptions = $button->getOperation()->getDefinition()->getDatagridOptions();

            if (!empty($datagridOptions['mass_action_provider'])) {
                $provider = $this->providerRegistry->getProvider($datagridOptions['mass_action_provider']);

                if ($provider) {
                    foreach ($provider->getActions() as $name => $massAction) {
                        $actions[$button->getName() . $name] = $massAction;
                    }
                }
            } elseif (!empty($datagridOptions['mass_action'])) {
                $actions[$button->getName()] = array_merge(
                    [
                        'label' => $this->translator->trans($button->getLabel(), [], $button->getTranslationDomain()),
                    ],
                    $datagridOptions['mass_action']
                );
            }
        }

        $config->offsetSet('mass_actions', $actions);
    }

    /**
     * @param DatagridConfiguration $config
     * @return ButtonSearchContext
     */
    protected function getButtonSearchContext(DatagridConfiguration $config)
    {
        $context = new ButtonSearchContext();
        $context
            ->setDatagrid($config->getName())
            ->setGroup($this->groups);

        if ($config->isOrmDatasource()) {
            $context->setEntity($config->getOrmQuery()->getRootEntity($this->entityClassResolver, true));
        }

        return $context;
    }
}
