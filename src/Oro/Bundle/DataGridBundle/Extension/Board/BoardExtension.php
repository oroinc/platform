<?php

namespace Oro\Bundle\DataGridBundle\Extension\Board;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Appearance\AppearanceExtension;
use Oro\Bundle\DataGridBundle\Extension\Board\Processor\BoardProcessorInterface;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds "board" mode to a datagrid.
 */
class BoardExtension extends AbstractExtension
{
    const CONFIG_PATH = 'board';

    const APPEARANCE_TYPE = 'board';

    const ENTITY_PAGINATION_PARAM = 'entity_pagination';
    const DEFAULT_ITEMS_PER_PAGE = 10;

    /**
     * Parameter used to handle pagination within board column
     * Should contain ids of board column to which a clicked entity belongs
     */
    const BOARD_COLUMNS_ID_PARAM_ID = 'boardColumnIds';

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var RequestStack */
    protected $requestStack;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RestrictionManager */
    protected $restrictionManager;

    /** @var Configuration */
    protected $configuration;

    /** @var EntityClassNameHelper */
    protected $entityClassNameHelper;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** {@inheritdoc} */
    protected $excludedModes = [
        DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE
    ];

    /** @var ContainerInterface */
    private $processorContainer;

    /** @var array */
    private $boards = [];

    public function __construct(
        ContainerInterface $processorContainer,
        AuthorizationCheckerInterface $authorizationChecker,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        RestrictionManager $restrictionManager,
        Configuration $configuration,
        EntityClassNameHelper $entityClassNameHelper,
        EntityClassResolver $entityClassResolver
    ) {
        $this->processorContainer = $processorContainer;
        $this->authorizationChecker = $authorizationChecker;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->restrictionManager = $restrictionManager;
        $this->configuration = $configuration;
        $this->entityClassNameHelper = $entityClassNameHelper;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        if (!parent::isApplicable($config)) {
            return false;
        }

        if (!$config->isOrmDatasource()) {
            return false;
        }

        if ($this->restrictionManager->boardViewEnabled($config)) {
            $this->initBoards($config);
        }
        if (empty($this->boards)) {
            $options = $config->offsetGetOr(AppearanceExtension::APPEARANCE_CONFIG_PATH, []);
            unset($options['board']);
            $config->offsetSet(AppearanceExtension::APPEARANCE_CONFIG_PATH, $options);
        }

        return !empty($this->boards);
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $boardOptions = [];
        foreach ($this->boards as $boardId => $boardConfig) {
            $processor = $this->getProcessor($boardConfig[Configuration::PROCESSOR_KEY]);
            $boardOptions[] = [
                'type' => static::APPEARANCE_TYPE,
                'plugin' => $boardConfig[Configuration::PLUGIN_KEY],
                'icon' => $boardConfig[Configuration::ICON_KEY],
                'id' => $boardId,
                'label' => $boardConfig[Configuration::LABEL_KEY]
                    ? $this->translator->trans($boardConfig[Configuration::LABEL_KEY])
                    : '',
                'group_by' => $boardConfig[Configuration::GROUP_KEY][Configuration::GROUP_PROPERTY_KEY],
                'columns' => $processor->getBoardOptions($boardConfig, $config),
                'default_transition' => $boardConfig[Configuration::TRANSITION_KEY],
                'board_view' => $boardConfig[Configuration::BOARD_VIEW_KEY],
                'card_view' => $boardConfig[Configuration::CARD_VIEW_KEY],
                'column_header_view' => $boardConfig[Configuration::HEADER_VIEW_KEY],
                'column_view' => $boardConfig[Configuration::COLUMN_VIEW_KEY],
                'readonly' => $this->isReadOnly($boardConfig),
                'toolbar' => $boardConfig[Configuration::TOOLBAR_KEY],
                'additional' => $boardConfig[Configuration::ADDITIONAL_KEY]
            ];
        }
        if ($boardOptions) {
            $data->offsetAddToArrayByPath(AppearanceExtension::APPEARANCE_OPTION_PATH, $boardOptions);
        }

        if ($this->isBoardEnabled()) {
            $this->overridePagerOptions($data);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        if ($this->isBoardEnabled()) {
            $appearanceData = $this->getAppearanceOption(AppearanceExtension::APPEARANCE_DATA_PARAM);
            if (!isset($this->boards[$appearanceData['id']])) {
                throw new RuntimeException(sprintf('Not defined board %s', $appearanceData['id']));
            }
            $boardConfig = $this->boards[$appearanceData['id']];
            $processor = $this->getProcessor($boardConfig[Configuration::PROCESSOR_KEY]);
            $appearanceData['property'] = $boardConfig[Configuration::GROUP_KEY][Configuration::GROUP_PROPERTY_KEY];
            $isPagination = $this->getParameters()->get(self::ENTITY_PAGINATION_PARAM);
            if ($isPagination) {
                $columnOptions = $this->getBoardColumnIds();
                if ($columnOptions) {
                    $appearanceData['column_options'] = $columnOptions;
                    $processor->processPaginationDatasource($datasource, $appearanceData, $config);
                }
            } else {
                if (empty($appearanceData['board_options'])) {
                    $boardOptions = $processor->getBoardOptions($boardConfig, $config);
                    $boardOptionsIds = array_column($boardOptions, 'ids');
                    $appearanceData['board_options'] = $boardOptionsIds;
                }
                $processor->processDatasource($datasource, $appearanceData, $config);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority()
    {
        // Should be processed after pager and sorting
        return -270;
    }

    /**
     * @return array
     */
    protected function getBoardColumnIds()
    {
        $ids = [];
        if ($this->requestStack) {
            $request = $this->requestStack->getCurrentRequest();
            $ids = json_decode($request->get(self::BOARD_COLUMNS_ID_PARAM_ID));
        }

        return $ids;
    }

    protected function getProcessor(string $name): BoardProcessorInterface
    {
        if (!$this->processorContainer->has($name)) {
            throw new RuntimeException(sprintf('Not found board processor %s', $name));
        }

        return $this->processorContainer->get($name);
    }

    /**
     * Normalize and process board configurations
     */
    protected function initBoards(DatagridConfiguration $config)
    {
        $appearanceConfig = $config->offsetGetOr(AppearanceExtension::APPEARANCE_CONFIG_PATH, []);
        if (empty($appearanceConfig[static::APPEARANCE_TYPE])) {
            return;
        }

        $boardConfig = $appearanceConfig[static::APPEARANCE_TYPE];
        foreach ($boardConfig as $boardId => $boardOptions) {
            $this->boards[$boardId] = $this->loadBoardOptions($config, $boardOptions);
        }
    }

    protected function loadBoardOptions(DatagridConfiguration $config, array $boardConfig): array
    {
        $result = $this->validateConfiguration($this->configuration, ['board' => $boardConfig]);
        if (!isset($result['default_transition']['save_api_accessor']['default_route_parameters']['className'])) {
            $entityName = $config->getOrmQuery()->getRootEntity($this->entityClassResolver, true);
            $result['default_transition']['save_api_accessor']['default_route_parameters']['className'] =
                $this->entityClassNameHelper->getUrlSafeClassName($entityName);
        }

        return $result;
    }

    /**
     * @param array $boardConfig
     *
     * @return bool
     */
    protected function isReadOnly($boardConfig)
    {
        if (!isset($boardConfig[Configuration::ACL_RESOURCE_KEY])) {
            return false;
        }

        return !$this->authorizationChecker->isGranted($boardConfig[Configuration::ACL_RESOURCE_KEY]);
    }

    /**
     * Returns if user selected board as a view right now
     *
     * @return bool returns true if board is selected false otherwise
     */
    protected function isBoardEnabled()
    {
        return static::APPEARANCE_TYPE === $this->getAppearanceOption(AppearanceExtension::APPEARANCE_TYPE_PARAM, '');
    }

    /**
     * Overrides pager settings to show correct number of items in every column
     */
    protected function overridePagerOptions(MetadataObject $data)
    {
        $itemsPerPage = [
            'pageSize' => static::DEFAULT_ITEMS_PER_PAGE
        ];

        $data->offsetAddToArray('initialState', $itemsPerPage);
        $data->offsetAddToArray('state', $itemsPerPage);
    }

    /**
     * Get param or return specified default value
     *
     * @param string $paramName
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getAppearanceOption($paramName, $default = null)
    {
        $appearanceParameters = $this->getParameters()->get(AppearanceExtension::APPEARANCE_ROOT_PARAM, []);

        return $appearanceParameters[$paramName] ?? $default;
    }
}
