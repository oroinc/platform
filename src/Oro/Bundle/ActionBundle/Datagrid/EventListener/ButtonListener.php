<?php

namespace Oro\Bundle\ActionBundle\Datagrid\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonsCollection;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Button\OperationButton;
use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderRegistry;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Extension\Action\Event\ConfigureActionsBefore;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class ButtonListener
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

    /** @var ButtonSearchContext */
    protected $searchContext;

    /** @var ButtonsCollection */
    protected $buttons;

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

    /**
     * @param ConfigureActionsBefore $event
     */
    public function onConfigureActions(ConfigureActionsBefore $event)
    {
        $config = $event->getConfig();

        // datasource types other than ORM are not handled
        if (!$config->isOrmDatasource()) {
            return;
        }

        $this->searchContext = $this->getButtonSearchContext($config);

        if (null === $this->buttons) {
            $this->buttons = $this->getButtons(
                $this->searchContext,
                $config->offsetGetOr(ActionExtension::ACTION_KEY, [])
            );
        }

        if (0 === count($this->buttons)) {
            return;
        }

        $this->processDatagridConfig($config);
        $this->processActionsConfig($config);
        $this->processMassActionsConfig($config);

        $config->offsetSet(
            ActionExtension::ACTION_CONFIGURATION_KEY,
            $this->getRowConfigurationClosure(
                $config->offsetGetOr(ActionExtension::ACTION_CONFIGURATION_KEY, [])
            )
        );
    }

    /**
     * Returns buttons if they not already exist in datagrid config as actions
     *
     * @param ButtonSearchContext $searchContext
     * @param array $datagridActionsConfig
     * @return ButtonsCollection
     */
    protected function getButtons(ButtonSearchContext $searchContext, array $datagridActionsConfig)
    {
        $buttonCollection = $this->buttonProvider->match($searchContext);

        return $buttonCollection->filter(
            function (ButtonInterface $button) use ($datagridActionsConfig) {
                return !array_key_exists(strtolower($button->getName()), $datagridActionsConfig);
            }
        );
    }

    /**
     * @param array|null|callable $actionConfiguration
     * @return \Closure
     */
    protected function getRowConfigurationClosure($actionConfiguration)
    {
        return function (ResultRecordInterface $record, array $config) use ($actionConfiguration) {
            $configuration = $this->retrieveConfiguration($actionConfiguration, $record, $config);

            $searchContext = clone $this->searchContext;
            $searchContext->setEntity($searchContext->getEntityClass(), $record->getValue('id'));

            $buttons = $this->buttons->map(
                function (
                    ButtonInterface $button,
                    ButtonProviderExtensionInterface $extension
                ) use (
                    $configuration,
                    $searchContext
                ) {
                    $name = strtolower($button->getName());
                    $enabled = false;
                    $newButton = clone $button;

                    if ($searchContext->getEntityId() === null) {
                        $configuration[$name] = false;
                    }

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
            $rowActions = call_user_func($actionConfiguration, $record, $config);
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

        $rowConfig = array_merge($frontendOptions['options'], $frontendOptions['data']);
        if ($button instanceof OperationButton) {
            $rowConfig = array_merge(
                $rowConfig,
                $this->optionsHelper->getExecutionTokenData($button->getOperation(), $button->getData())
            );
        }

        return $rowConfig;
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processDatagridConfig(DatagridConfiguration $config)
    {
        $context = $this->contextHelper->getContext();

        if (!empty($context['route'])) {
            $config->offsetSetByPath('[options][urlParams][originalRoute]', $context['route']);
        }
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processActionsConfig(DatagridConfiguration $config)
    {
        $actionsConfig = $config->offsetGetOr(ActionExtension::ACTION_KEY, []);

        foreach ($this->buttons as $button) {
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
     */
    protected function processMassActionsConfig(DatagridConfiguration $config)
    {
        $actions = $config->offsetGetOr('mass_actions', []);

        foreach ($this->buttons->getIterator() as $button) {
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
        $context->setDatagrid($config->getName())
            ->setEntity($config->getOrmQuery()->getRootEntity($this->entityClassResolver, true))
            ->setGroup($this->groups);

        return $context;
    }
}
