<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Displays entity configuration.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DebugCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:entity-config:debug';

    private ManagerRegistry $registry;
    private ConfigManager $configManager;

    public function __construct(ManagerRegistry $registry, ConfigManager $configManager)
    {
        parent::__construct();

        $this->registry = $registry;
        $this->configManager = $configManager;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addArgument('entity', InputArgument::OPTIONAL, 'Entity class name')
            ->addArgument('field', InputArgument::OPTIONAL, 'Field name')
            ->addOption('cache', null, InputOption::VALUE_NONE, 'Load configuration from cache (instead of database)')
            ->addOption('scope', null, InputOption::VALUE_REQUIRED, 'Attribute scope')
            ->addOption('attr', null, InputOption::VALUE_REQUIRED, 'Attribute name')
            ->addOption('val', null, InputOption::VALUE_REQUIRED, 'Attribute value')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List the configurable entities or fields')
            ->addOption('set', null, InputOption::VALUE_NONE, 'Set an attribute value')
            ->addOption('remove', null, InputOption::VALUE_NONE, 'Remove an attribute/scope')
            ->addOption(
                'ref-non-configurable',
                null,
                InputOption::VALUE_NONE,
                'Show fields that are references to non-configurable entities'
            )
            ->setDescription('Displays entity configuration.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command displays entity configuration.

  <info>php %command.full_name%</info>

The entity class name and field name can be provided as arguments
to see only the related configuration:

  <info>php %command.full_name% <entity-class></info>
  <info>php %command.full_name% <entity-class> <field-name></info>

The <info>--list</info> option can be used to see a list of the configurable entities,
or a list of fields of a specific entity:

  <info>php %command.full_name% --list</info>
  <info>php %command.full_name% --list <entity-class></info>

The <info>--ref-non-configurable</info> option can be used to show the fields that are
references to non-configurable entities:

  <info>php %command.full_name% --ref-non-configurable <entity-class></info>

The <info>--cache</info> option can be used to load the entity configuration from cache
(instead of from the database):

  <info>php %command.full_name% --cache --scope=<attribute-scope></info>

The <info>--scope</info> and <info>--attr</info> options can be used to load or apply the changes
to the specified attribute scope and attribute:

  <info>php %command.full_name% --scope=<attribute-scope></info>
  <info>php %command.full_name% --attr=<attribute-name></info>

The <info>--set</info> and <info>--val</info> options can be used to update an attribute value:

  <info>php %command.full_name% --attr=<attribute-name> --set --val=<value></info>

The <info>--remove</info> option can be used to remove an attribute scope or an attribute:

  <info>php %command.full_name% --remove --scope=<attribute-scope></info>
  <info>php %command.full_name% --remove --attr=<attribute-name></info>

HELP
            )
            ->addUsage('<entity-class>')
            ->addUsage('<entity-class> <field-name>')
            ->addUsage('--list')
            ->addUsage('--list <entity-class>')
            ->addUsage('--ref-non-configurable <entity-class>')
            ->addUsage('--cache --scope=<scope>')
            ->addUsage('--scope=<attribute-scope>')
            ->addUsage('--attr=<attribute-name>')
            ->addUsage('--set --attr=<attribute-name> --val=<value>')
            ->addUsage('--remove --scope=<attribute-scope>')
            ->addUsage('--remove --attr=<attribute-name>')
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $entity         = $input->getArgument('entity');
        $field          = $input->getArgument('field');
        $scope          = $input->getOption('scope');
        $attrName       = $input->getOption('attr');
        $attrVal        = $input->getOption('val');
        $isCache        = $input->getOption('cache');
        $isList         = $input->getOption('list');
        $isSet          = $input->getOption('set');
        $isRemove       = $input->getOption('remove');
        $isRefNonConfig = $input->getOption('ref-non-configurable');

        if ($isCache) {
            if (empty($scope)) {
                throw new \RuntimeException('The "--cache" option must be used together with "--scope" option.');
            }
            if ($isSet) {
                throw new \RuntimeException('The "--cache" option cannot be be used with "--set" option.');
            }
            if ($isRemove) {
                throw new \RuntimeException('The "--cache" option cannot be be used with "--remove" option.');
            }
            if ($isRefNonConfig) {
                throw new \RuntimeException(
                    'The "--cache" option cannot be be used with "--ref-non-configurable" option.'
                );
            }
            if ($isList) {
                if (empty($entity)) {
                    $this->dumpEntityListFromCache($io, $scope);
                } else {
                    $this->dumpFieldListFromCache($io, $entity, $scope);
                }
            } elseif (!empty($entity)) {
                if (empty($field)) {
                    $this->dumpEntityConfigFromCache($io, $entity, $scope, $attrName);
                } else {
                    $this->dumpFieldConfigFromCache($io, $entity, $field, $scope, $attrName);
                }
            }
        } else {
            if ($isList) {
                if (empty($entity)) {
                    $this->dumpEntityList($io);
                } else {
                    $this->dumpFieldList($io, $entity);
                }
            } elseif ($isRefNonConfig) {
                $this->dumpNonConfigRef($io, $entity);
            } elseif (!empty($entity)) {
                if ($isSet && empty($scope) && empty($attrName) && $attrVal === null) {
                    throw new \RuntimeException(
                        'The "--set" option cannot be be used without "--scope", "--attr" and "--val" options.'
                    );
                }
                if ($isRemove && empty($scope)) {
                    throw new \RuntimeException(
                        'The "--remove" option cannot be be used without "--scope" option.'
                    );
                }
                if ($isSet) {
                    if (empty($field)) {
                        $this->setEntityConfigValue($io, $entity, $scope, $attrName, $attrVal);
                    } else {
                        $this->setFieldConfigValue($io, $entity, $field, $scope, $attrName, $attrVal);
                    }
                } elseif ($isRemove) {
                    if (empty($field)) {
                        $this->removeEntityConfigScopeOrAttribute($io, $entity, $scope, $attrName);
                    } else {
                        $this->removeFieldConfigScopeOrAttribute($io, $entity, $field, $scope, $attrName);
                    }
                } else {
                    if (empty($field)) {
                        $this->dumpEntityConfig($io, $entity, $scope, $attrName);
                    } else {
                        $this->dumpFieldConfig($io, $entity, $field, $scope, $attrName);
                    }
                }
            }
        }

        return 0;
    }

    protected function dumpEntityList(OutputInterface $output): void
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(EntityConfigModel::class);

        $rows = $em->getConnection()->fetchAll(
            'SELECT class_name, mode FROM oro_entity_config ORDER BY class_name'
        );
        foreach ($rows as $row) {
            $output->writeln(sprintf('%s, Mode: %s', $row['class_name'], $row['mode']));
        }
    }

    protected function dumpEntityListFromCache(OutputInterface $output, string $scope): void
    {
        /** @var EntityConfigId[] $ids */
        $ids = $this->configManager->getIds($scope, null, true);
        /** @var EntityConfigId[] $notHiddenIds */
        $notHiddenIds = $this->configManager->getIds($scope, null);

        foreach ($ids as $id) {
            $hidden = true;
            foreach ($notHiddenIds as $notHiddenId) {
                if ($id->getClassName() === $notHiddenId->getClassName()) {
                    $hidden = false;
                    break;
                }
            }
            $output->writeln(
                sprintf(
                    '%s, Hidden: %s',
                    $id->getClassName(),
                    $hidden ? 'Yes' : 'No'
                )
            );
        }
    }

    protected function dumpFieldList(OutputInterface $output, string $className): void
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(EntityConfigModel::class);

        $rows = $em->getConnection()->fetchAll(
            'SELECT fc.field_name, fc.type, fc.mode FROM oro_entity_config ec'
            . ' INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id'
            . ' WHERE ec.class_name = ?'
            . ' ORDER BY fc.field_name',
            [$className],
            ['string']
        );
        foreach ($rows as $row) {
            $output->writeln(sprintf('%s, Type: %s, Mode: %s', $row['field_name'], $row['type'], $row['mode']));
        }
    }

    protected function dumpFieldListFromCache(OutputInterface $output, string $className, string $scope): void
    {
        /** @var FieldConfigId[] $ids */
        $ids = $this->configManager->getIds($scope, $className, true);
        /** @var FieldConfigId[] $notHiddenIds */
        $notHiddenIds = $this->configManager->getIds($scope, $className);

        foreach ($ids as $id) {
            $hidden = true;
            foreach ($notHiddenIds as $notHiddenId) {
                if ($id->getClassName() === $notHiddenId->getClassName()
                    && $id->getFieldName() === $notHiddenId->getFieldName()
                ) {
                    $hidden = false;
                    break;
                }
            }
            $output->writeln(
                sprintf(
                    '%s, Type: %s, Hidden: %s',
                    $id->getFieldName(),
                    $id->getFieldType(),
                    $hidden ? 'Yes' : 'No'
                )
            );
        }
    }

    protected function dumpNonConfigRef(OutputInterface $output, ?string $className = null): void
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(EntityConfigModel::class);

        if (empty($className)) {
            $rows       = $em->getConnection()->fetchAll(
                'SELECT class_name FROM oro_entity_config ORDER BY class_name'
            );
            $classNames = [];
            foreach ($rows as $row) {
                $classNames[$row['class_name']] = $row['class_name'];
            }
        } else {
            $classNames = [$className => $className];
        }

        $associationMapping = [
            ClassMetadataInfo::ONE_TO_ONE   => RelationType::ONE_TO_ONE,
            ClassMetadataInfo::MANY_TO_ONE  => RelationType::MANY_TO_ONE,
            ClassMetadataInfo::ONE_TO_MANY  => RelationType::ONE_TO_MANY,
            ClassMetadataInfo::MANY_TO_MANY => RelationType::MANY_TO_MANY,
        ];

        foreach ($classNames as $className) {
            $classMetadata     = $em->getClassMetadata($className);
            $assocNames        = $classMetadata->getAssociationNames();
            $isClassNameDumped = false;
            foreach ($assocNames as $assocName) {
                $targetClass = $classMetadata->getAssociationTargetClass($assocName);
                if (!isset($classNames[$targetClass])) {
                    if (!$isClassNameDumped) {
                        $isClassNameDumped = true;
                        $output->writeln(sprintf('%s:', $className));
                    }
                    $fieldInfo = $em->getConnection()->fetchAll(
                        'SELECT fc.type FROM oro_entity_config ec'
                        . ' INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id'
                        . ' WHERE ec.class_name = ? AND fc.field_name = ?',
                        [$className, $assocName],
                        ['string', 'string']
                    );

                    $assocType = '';
                    if (isset($fieldInfo[0]['type'])) {
                        $assocType = $fieldInfo[0]['type'];
                    } elseif ($classMetadata->hasAssociation($assocName)) {
                        $assocMapping = $classMetadata->getAssociationMapping($assocName);
                        $assocType = $associationMapping[$assocMapping['type']];
                    }

                    $output->writeln(
                        sprintf(
                            '  %s, %s, ref to %s',
                            $assocName,
                            $assocType,
                            $targetClass
                        )
                    );
                }
            }
        }
    }

    protected function dumpEntityConfig(
        SymfonyStyle $output,
        string $className,
        ?string $scope = null,
        ?string $attrName = null
    ): void {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(EntityConfigModel::class);

        /** @var Connection $connection */
        $connection = $em->getConnection();

        $rows = $connection->fetchAll(
            'SELECT * FROM oro_entity_config WHERE class_name = ?',
            [$className],
            ['string']
        );
        foreach ($rows as $row) {
            $output->writeln(sprintf('Class: %s', $row['class_name']));
            $output->writeln(sprintf('Mode:  %s', $row['mode']));
            $output->title('Values:');
            $this->dumpData($output, $connection->convertToPHPValue($row['data'], 'array'), $scope, $attrName);
        }
    }

    protected function dumpEntityConfigFromCache(
        SymfonyStyle $output,
        string $className,
        string $scope,
        ?string $attrName = null
    ): void {
        /** @var ConfigProvider $cp */
        $cp = $this->configManager->getProvider($scope);

        if (!$cp->hasConfig($className)) {
            $output->writeln('The configuration was not found.');
        }

        $config = $cp->getConfig($className);

        $output->writeln(sprintf('Class: %s', $config->getId()->getClassName()));
        $output->title('Values:');
        $this->dumpConfig($output, $config, $attrName);
    }

    protected function dumpFieldConfig(
        SymfonyStyle $output,
        string $className,
        string $fieldName,
        ?string $scope = null,
        ?string $attrName = null
    ): void {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(EntityConfigModel::class);

        /** @var Connection $connection */
        $connection = $em->getConnection();

        $rows = $connection->fetchAll(
            'SELECT ec.class_name, fc.* FROM oro_entity_config ec'
            . ' INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id'
            . ' WHERE ec.class_name = ? AND fc.field_name = ?',
            [$className, $fieldName],
            ['string', 'string']
        );
        foreach ($rows as $row) {
            $output->writeln(sprintf('Class: %s', $row['class_name']));
            $output->writeln(sprintf('Field: %s', $row['field_name']));
            $output->writeln(sprintf('Type:  %s', $row['type']));
            $output->writeln(sprintf('Mode:  %s', $row['mode']));
            $output->title('Values:');
            $this->dumpData($output, $connection->convertToPHPValue($row['data'], 'array'), $scope, $attrName);
        }
    }

    protected function dumpFieldConfigFromCache(
        SymfonyStyle $output,
        string $className,
        string $fieldName,
        string $scope,
        ?string $attrName = null
    ): void {
        /** @var ConfigProvider $cp */
        $cp = $this->configManager->getProvider($scope);

        if (!$cp->hasConfig($className, $fieldName)) {
            $output->writeln('The configuration was not found.');
        }

        $config = $cp->getConfig($className, $fieldName);

        $output->writeln(sprintf('Class: %s', $config->getId()->getClassName()));
        $output->writeln(sprintf('Field: %s', $config->getId()->getFieldName()));
        $output->writeln(sprintf('Type:  %s', $config->getId()->getFieldType()));
        $output->title('Values:');
        $this->dumpConfig($output, $config, $attrName);
    }

    protected function dumpData(
        OutputInterface $output,
        array $data,
        ?string $scope = null,
        ?string $attrName = null
    ): void {
        $res = $data;
        if (!empty($scope)) {
            if (isset($data[$scope])) {
                $res = [$scope => $data[$scope]];
                if (!empty($attrName)
                    && (isset($data[$scope][$attrName]) || array_key_exists($attrName, $data[$scope]))
                ) {
                    $res = [$scope => [$attrName => $data[$scope][$attrName]]];
                }
            }
        }
        $output->writeln($this->convertArrayToString($res));
    }

    protected function dumpConfig(OutputInterface $output, ConfigInterface $config, ?string $attrName = null): void
    {
        $data = $config->all();
        $res  = [$config->getId()->getScope() => $data];
        if (!empty($attrName) && (isset($data[$attrName]) || array_key_exists($attrName, $data))) {
            $res = [$config->getId()->getScope() => [$attrName => $data[$attrName]]];
        }
        $output->writeln($this->convertArrayToString($res));
    }

    protected function removeEntityConfigScopeOrAttribute(
        OutputInterface $output,
        string $className,
        string $scope,
        ?string $attrName = null
    ): void {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(EntityConfigModel::class);

        /** @var Connection $connection */
        $connection = $em->getConnection();

        $rows = $connection->fetchAll(
            'SELECT * FROM oro_entity_config WHERE class_name = ?',
            [$className],
            ['string']
        );
        if (empty($rows)) {
            $output->writeln('The configuration was not found.');
        }

        foreach ($rows as $row) {
            $data = $connection->convertToPHPValue($row['data'], 'array');
            if (empty($attrName)) {
                unset($data[$scope]);
            } else {
                unset($data[$scope][$attrName]);
            }

            $connection->executeStatement(
                'UPDATE oro_entity_config SET data = :data WHERE id = :id',
                ['data' => $data, 'id' => $row['id']],
                ['data' => 'array', 'id' => 'integer']
            );
        }

        $this->clearConfigCache();
    }

    protected function removeFieldConfigScopeOrAttribute(
        OutputInterface $output,
        string $className,
        string $fieldName,
        string $scope,
        ?string $attrName = null
    ): void {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(EntityConfigModel::class);

        /** @var Connection $connection */
        $connection = $em->getConnection();

        $rows = $connection->fetchAll(
            'SELECT fc.* FROM oro_entity_config ec'
            . ' INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id'
            . ' WHERE ec.class_name = ? AND fc.field_name = ?',
            [$className, $fieldName],
            ['string', 'string']
        );
        if (empty($rows)) {
            $output->writeln('The configuration was not found.');
        }

        foreach ($rows as $row) {
            $data = $connection->convertToPHPValue($row['data'], 'array');
            if (empty($attrName)) {
                unset($data[$scope]);
            } else {
                unset($data[$scope][$attrName]);
            }

            $connection->executeStatement(
                'UPDATE oro_entity_config_field SET data = :data WHERE id = :id',
                ['data' => $data, 'id' => $row['id']],
                ['data' => 'array', 'id' => 'integer']
            );
        }

        $this->clearConfigCache();
    }

    /**
     * @param mixed $attrVal
     */
    protected function setEntityConfigValue(
        OutputInterface $output,
        string $className,
        string $scope,
        string $attrName,
        $attrVal
    ): void {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(EntityConfigModel::class);

        /** @var Connection $connection */
        $connection = $em->getConnection();

        $rows = $connection->fetchAll(
            'SELECT * FROM oro_entity_config WHERE class_name = ?',
            [$className],
            ['string']
        );
        if (empty($rows)) {
            $output->writeln('The configuration was not found.');
        }

        foreach ($rows as $row) {
            $data                    = $connection->convertToPHPValue($row['data'], 'array');
            $data[$scope][$attrName] = $this->getTypedVal($attrVal);

            $connection->executeStatement(
                'UPDATE oro_entity_config SET data = :data WHERE id = :id',
                ['data' => $data, 'id' => $row['id']],
                ['data' => 'array', 'id' => 'integer']
            );
        }

        $this->clearConfigCache();
    }

    /**
     * @param mixed $attrVal
     */
    protected function setFieldConfigValue(
        OutputInterface $output,
        string $className,
        string $fieldName,
        string $scope,
        string $attrName,
        $attrVal
    ): void {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(EntityConfigModel::class);

        /** @var Connection $connection */
        $connection = $em->getConnection();

        $rows = $connection->fetchAll(
            'SELECT fc.* FROM oro_entity_config ec'
            . ' INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id'
            . ' WHERE ec.class_name = ? AND fc.field_name = ?',
            [$className, $fieldName],
            ['string', 'string']
        );
        if (empty($rows)) {
            $output->writeln('The configuration was not found.');
        }

        foreach ($rows as $row) {
            $data                    = $connection->convertToPHPValue($row['data'], 'array');
            $data[$scope][$attrName] = $this->getTypedVal($attrVal);

            $connection->executeStatement(
                'UPDATE oro_entity_config_field SET data = :data WHERE id = :id',
                ['data' => $data, 'id' => $row['id']],
                ['data' => 'array', 'id' => 'integer']
            );
        }

        $this->clearConfigCache();
    }

    /**
     * @param mixed $val
     *
     * @return mixed
     */
    protected function getTypedVal($val)
    {
        if (!is_string($val)) {
            return $val;
        }

        if ($val === 'NULL') {
            return null;
        } elseif ($val === 'true') {
            return true;
        } elseif ($val === 'false') {
            return false;
        } elseif (is_numeric($val)) {
            if ($val == intval($val)) {
                return intval($val);
            } else {
                return floatval($val);
            }
        }

        return $val;
    }

    protected function clearConfigCache()
    {
        $this->configManager->clearCache();
    }

    /**
     * Converts array to string and replace boolean values and null to appropriate strings equivalents
     *
     * @param array $array
     *
     * @return mixed
     */
    protected function convertArrayToString(array $array)
    {
        $array = $this->sortDataByKeys($array);

        return Yaml::dump($array, 5, 4, Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE | Yaml::DUMP_OBJECT);
    }

    private function sortDataByKeys(array $array): array
    {
        if (ArrayUtil::isAssoc($array)) {
            ksort($array);
        }
        foreach ($array as &$val) {
            if (is_array($val) && ArrayUtil::isAssoc($val)) {
                ksort($val);
            }
        }

        return $array;
    }
}
