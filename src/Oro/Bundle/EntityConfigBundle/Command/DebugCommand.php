<?php

namespace Oro\Bundle\EntityConfigBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-config:debug')
            ->addArgument('entity', InputArgument::OPTIONAL, 'The entity class name')
            ->addArgument('field', InputArgument::OPTIONAL, 'The field name')
            ->addOption(
                'cache',
                null,
                InputOption::VALUE_NONE,
                'Show configuration values from a cache. By default values are loaded from a database'
            )
            ->addOption(
                'scope',
                null,
                InputOption::VALUE_REQUIRED,
                'The attribute scope'
            )
            ->addOption(
                'attr',
                null,
                InputOption::VALUE_REQUIRED,
                'The attribute name'
            )
            ->addOption(
                'val',
                null,
                InputOption::VALUE_REQUIRED,
                'The attribute value'
            )
            ->addOption(
                'list',
                'l',
                InputOption::VALUE_NONE,
                'Show the list of configurable entities or fields'
            )
            ->addOption(
                'set',
                null,
                InputOption::VALUE_NONE,
                'Sets an attribute value of configurable entities or fields'
            )
            ->addOption(
                'remove',
                null,
                InputOption::VALUE_NONE,
                'Removes an attribute/scope from configurable entities or fields'
            )
            ->addOption(
                'ref-non-configurable',
                null,
                InputOption::VALUE_NONE,
                'Show all fields that are references to non configurable entities'
            )
            ->setDescription('Displays entity configuration.');
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
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
                    $this->dumpEntityListFromCache($output, $scope);
                } else {
                    $this->dumpFieldListFromCache($output, $entity, $scope);
                }
            } elseif (!empty($entity)) {
                if (empty($field)) {
                    $this->dumpEntityConfigFromCache($output, $entity, $scope, $attrName);
                } else {
                    $this->dumpFieldConfigFromCache($output, $entity, $field, $scope, $attrName);
                }
            }
        } else {
            if ($isList) {
                if (empty($entity)) {
                    $this->dumpEntityList($output);
                } else {
                    $this->dumpFieldList($output, $entity);
                }
            } elseif ($isRefNonConfig) {
                $this->dumpNonConfigRef($output, $entity);
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
                        $this->setEntityConfigValue($output, $entity, $scope, $attrName, $attrVal);
                    } else {
                        $this->setFieldConfigValue($output, $entity, $field, $scope, $attrName, $attrVal);
                    }
                } elseif ($isRemove) {
                    if (empty($field)) {
                        $this->removeEntityConfigScopeOrAttribute($output, $entity, $scope, $attrName);
                    } else {
                        $this->removeFieldConfigScopeOrAttribute($output, $entity, $field, $scope, $attrName);
                    }
                } else {
                    if (empty($field)) {
                        $this->dumpEntityConfig($output, $entity, $scope, $attrName);
                    } else {
                        $this->dumpFieldConfig($output, $entity, $field, $scope, $attrName);
                    }
                }
            }
        }
    }

    /**
     * @param OutputInterface $output
     */
    protected function dumpEntityList(OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $rows = $em->getConnection()->fetchAll(
            'SELECT class_name, mode FROM oro_entity_config ORDER BY class_name'
        );
        foreach ($rows as $row) {
            $output->writeln(sprintf('%s, Mode: %s', $row['class_name'], $row['mode']));
        }
    }

    /**
     * @param OutputInterface $output
     * @param string          $scope
     */
    protected function dumpEntityListFromCache(OutputInterface $output, $scope)
    {
        /** @var ConfigManager $cm */
        $cm = $this->getContainer()->get('oro_entity_config.config_manager');

        /** @var EntityConfigId[] $ids */
        $ids = $cm->getIds($scope, null, true);
        /** @var EntityConfigId[] $notHiddenIds */
        $notHiddenIds = $cm->getIds($scope, null);

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

    /**
     * @param OutputInterface $output
     * @param string          $className
     */
    protected function dumpFieldList(OutputInterface $output, $className)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

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

    /**
     * @param OutputInterface $output
     * @param string          $className
     * @param string          $scope
     */
    protected function dumpFieldListFromCache(OutputInterface $output, $className, $scope)
    {
        /** @var ConfigManager $cm */
        $cm = $this->getContainer()->get('oro_entity_config.config_manager');

        /** @var FieldConfigId[] $ids */
        $ids = $cm->getIds($scope, $className, true);
        /** @var FieldConfigId[] $notHiddenIds */
        $notHiddenIds = $cm->getIds($scope, $className);

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

    /**
     * @param OutputInterface $output
     * @param string|null     $className
     */
    protected function dumpNonConfigRef(OutputInterface $output, $className = null)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

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

    /**
     * @param OutputInterface $output
     * @param string          $className
     * @param string|null     $scope
     * @param string|null     $attrName
     */
    protected function dumpEntityConfig(OutputInterface $output, $className, $scope = null, $attrName = null)
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine.orm.entity_manager')->getConnection();

        $rows = $connection->fetchAll(
            'SELECT * FROM oro_entity_config WHERE class_name = ?',
            [$className],
            ['string']
        );
        foreach ($rows as $row) {
            $output->writeln(sprintf('Class: %s', $row['class_name']));
            $output->writeln(sprintf('Mode:  %s', $row['mode']));
            $output->writeln('Values:');
            $this->dumpData($output, $connection->convertToPHPValue($row['data'], 'array'), $scope, $attrName);
        }
    }

    /**
     * @param OutputInterface $output
     * @param string          $className
     * @param string          $scope
     * @param string|null     $attrName
     */
    protected function dumpEntityConfigFromCache(OutputInterface $output, $className, $scope, $attrName = null)
    {
        /** @var ConfigProvider $cp */
        $cp = $this->getContainer()->get('oro_entity_config.config_manager')->getProvider($scope);

        if (!$cp->hasConfig($className)) {
            $output->writeln('The configuration was not found.');
        }

        $config = $cp->getConfig($className);

        $output->writeln(sprintf('Class: %s', $config->getId()->getClassName()));
        $output->writeln('Values:');
        $this->dumpConfig($output, $config, $attrName);
    }

    /**
     * @param OutputInterface $output
     * @param string          $className
     * @param string          $fieldName
     * @param string|null     $scope
     * @param string|null     $attrName
     */
    protected function dumpFieldConfig(OutputInterface $output, $className, $fieldName, $scope = null, $attrName = null)
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine.orm.entity_manager')->getConnection();

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
            $output->writeln('Values:');
            $this->dumpData($output, $connection->convertToPHPValue($row['data'], 'array'), $scope, $attrName);
        }
    }

    /**
     * @param OutputInterface $output
     * @param string          $className
     * @param string          $fieldName
     * @param string          $scope
     * @param string|null     $attrName
     */
    protected function dumpFieldConfigFromCache(
        OutputInterface $output,
        $className,
        $fieldName,
        $scope,
        $attrName = null
    ) {
        /** @var ConfigProvider $cp */
        $cp = $this->getContainer()->get('oro_entity_config.config_manager')->getProvider($scope);

        if (!$cp->hasConfig($className, $fieldName)) {
            $output->writeln('The configuration was not found.');
        }

        $config = $cp->getConfig($className, $fieldName);

        $output->writeln(sprintf('Class: %s', $config->getId()->getClassName()));
        $output->writeln(sprintf('Field: %s', $config->getId()->getFieldName()));
        $output->writeln(sprintf('Type:  %s', $config->getId()->getFieldType()));
        $output->writeln('Values:');
        $this->dumpConfig($output, $config, $attrName);
    }

    /**
     * @param OutputInterface $output
     * @param array           $data
     * @param string|null     $scope
     * @param string|null     $attrName
     */
    protected function dumpData(OutputInterface $output, array $data, $scope = null, $attrName = null)
    {
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

    /**
     * @param OutputInterface $output
     * @param ConfigInterface $config
     * @param string|null     $attrName
     */
    protected function dumpConfig(OutputInterface $output, ConfigInterface $config, $attrName = null)
    {
        $data = $config->all();
        $res  = [$config->getId()->getScope() => $data];
        if (!empty($attrName) && (isset($data[$attrName]) || array_key_exists($attrName, $data))) {
            $res = [$config->getId()->getScope() => [$attrName => $data[$attrName]]];
        }
        $output->writeln($this->convertArrayToString($res));
    }

    /**
     * @param OutputInterface $output
     * @param string          $className
     * @param string          $scope
     * @param string|null     $attrName
     */
    protected function removeEntityConfigScopeOrAttribute(
        OutputInterface $output,
        $className,
        $scope,
        $attrName = null
    ) {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine.orm.entity_manager')->getConnection();

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

            $connection->executeUpdate(
                'UPDATE oro_entity_config SET data = :data WHERE id = :id',
                ['data' => $data, 'id' => $row['id']],
                ['data' => 'array', 'id' => 'integer']
            );
        }

        $this->clearConfigCache();
    }

    /**
     * @param OutputInterface $output
     * @param string          $className
     * @param string          $fieldName
     * @param string          $scope
     * @param string|null     $attrName
     */
    protected function removeFieldConfigScopeOrAttribute(
        OutputInterface $output,
        $className,
        $fieldName,
        $scope,
        $attrName = null
    ) {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine.orm.entity_manager')->getConnection();

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

            $connection->executeUpdate(
                'UPDATE oro_entity_config_field SET data = :data WHERE id = :id',
                ['data' => $data, 'id' => $row['id']],
                ['data' => 'array', 'id' => 'integer']
            );
        }

        $this->clearConfigCache();
    }

    /**
     * @param OutputInterface $output
     * @param string          $className
     * @param string          $scope
     * @param string          $attrName
     * @param mixed           $attrVal
     */
    protected function setEntityConfigValue(
        OutputInterface $output,
        $className,
        $scope,
        $attrName,
        $attrVal
    ) {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine.orm.entity_manager')->getConnection();

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

            $connection->executeUpdate(
                'UPDATE oro_entity_config SET data = :data WHERE id = :id',
                ['data' => $data, 'id' => $row['id']],
                ['data' => 'array', 'id' => 'integer']
            );
        }

        $this->clearConfigCache();
    }

    /**
     * @param OutputInterface $output
     * @param string          $className
     * @param string          $fieldName
     * @param string          $scope
     * @param string          $attrName
     * @param mixed           $attrVal
     */
    protected function setFieldConfigValue(
        OutputInterface $output,
        $className,
        $fieldName,
        $scope,
        $attrName,
        $attrVal
    ) {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine.orm.entity_manager')->getConnection();

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

            $connection->executeUpdate(
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
        /** @var ConfigManager $cm */
        $cm = $this->getContainer()->get('oro_entity_config.config_manager');
        $cm->clearCache();
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
        $replace = [
            false => 'false',
            true  => 'true',
            null  => 'NULL',
        ];

        array_walk_recursive(
            $array,
            function (&$item) use ($replace) {
                if (is_bool($item) || is_null($item)) {
                    $item = $replace[$item];
                }
            }
        );

        return print_r($array, true);
    }
}
