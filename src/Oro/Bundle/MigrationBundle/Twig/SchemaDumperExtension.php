<?php

namespace Oro\Bundle\MigrationBundle\Twig;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function used in generator of data migration classes:
 *   - oro_migration_get_schema_column_options
 */
class SchemaDumperExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private ?AbstractPlatform $platform = null;
    private ?Column $defaultColumn = null;
    private array $defaultColumnOptions = [];
    private array $optionNames = [
        'default',
        'notnull',
        'length',
        'precision',
        'scale',
        'fixed',
        'unsigned',
        'autoincrement'
    ];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_migration_get_schema_column_options', [$this, 'getColumnOptions']),
        ];
    }

    public function getColumnOptions(Column $column): array
    {
        $defaultOptions = $this->getDefaultOptions();
        $platform = $this->getPlatform();
        $options = [];

        foreach ($this->optionNames as $optionName) {
            $value = $this->getColumnOption($column, $optionName);
            if ($value !== $defaultOptions[$optionName]) {
                $options[$optionName] = $value;
            }
        }

        $comment = $column->getComment();
        if ($platform && $platform->isCommentedDoctrineType($column->getType())) {
            $comment .= $platform->getDoctrineTypeComment($column->getType());
        }
        if (!empty($comment)) {
            $options['comment'] = $comment;
        }

        return $options;
    }

    private function getColumnOption(Column $column, string $optionName): mixed
    {
        $method = 'get' . $optionName;

        return $column->$method();
    }

    private function getPlatform(): AbstractPlatform
    {
        if (null === $this->platform) {
            $this->platform = $this->container->get(ManagerRegistry::class)
                ->getConnection()
                ->getDatabasePlatform();
        }

        return $this->platform;
    }

    private function getDefaultOptions(): array
    {
        if (null === $this->defaultColumn) {
            $this->defaultColumn = new Column('_template_', Type::getType(Types::STRING));
        }
        if (!$this->defaultColumnOptions) {
            foreach ($this->optionNames as $optionName) {
                $this->defaultColumnOptions[$optionName] = $this->getColumnOption($this->defaultColumn, $optionName);
            }
        }

        return $this->defaultColumnOptions;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            ManagerRegistry::class,
        ];
    }
}
