<?php
declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Base class for CLI commands that show various debug information about API configuration.
 */
abstract class AbstractDebugCommand extends Command
{
    protected ValueNormalizer $valueNormalizer;
    protected ResourcesProvider $resourcesProvider;

    public function __construct(ValueNormalizer $valueNormalizer, ResourcesProvider $resourcesProvider)
    {
        parent::__construct();

        $this->valueNormalizer = $valueNormalizer;
        $this->resourcesProvider = $resourcesProvider;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption(
                'request-type',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Request type',
                $this->getDefaultRequestType()
            )
            ->setHelp(
                // @codingStandardsIgnoreStart
                $this->getHelp() .
                <<<'HELP'

The <info>--request-type</info> option can limit the scope to the specified request type(s).
Omitting this option is equivalent to <info>--request-type=rest --request-type=json_api</info>.
Available types: <comment>rest</comment>, <comment>json_api</comment>, <comment>batch</comment>, or use <comment>any</comment> to include all request types:

  <info>php %command.full_name% --request-type=rest</info> <fg=green;options=underscore>other options and arguments</>
  <info>php %command.full_name% --request-type=json_api</info> <fg=green;options=underscore>other options and arguments</>
  <info>php %command.full_name% --request-type=batch</info> <fg=green;options=underscore>other options and arguments</>
  <info>php %command.full_name% --request-type=any</info> <fg=green;options=underscore>other options and arguments</>

HELP
                // @codingStandardsIgnoreEnd
            )
            ->addUsage('--request-type=rest [other options and arguments]')
            ->addUsage('--request-type=json_api [other options and arguments]')
            ->addUsage('--request-type=batch [other options and arguments]')
            ->addUsage('--request-type=any [other options and arguments]')
        ;
    }

    /**
     * @return string[]
     */
    protected function getDefaultRequestType(): array
    {
        return [RequestType::REST, RequestType::JSON_API];
    }

    protected function getRequestType(InputInterface $input): RequestType
    {
        $value = $input->getOption('request-type');
        if (count($value) === 1 && 'any' === $value[0]) {
            $value = [];
        }

        return new RequestType($value);
    }

    /**
     * @param mixed $value
     */
    protected function convertValueToString($value): string
    {
        if (null === $value) {
            return 'NULL';
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            return '[' . implode(', ', $value) . ']';
        }

        return (string)$value;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    protected function getTypedValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        if (in_array($value, ['NULL', 'null', '~'], true)) {
            return null;
        } elseif ('true' === $value) {
            return true;
        } elseif ('false' === $value) {
            return false;
        } elseif (is_numeric($value)) {
            return $value == (int)$value
                ? (int)$value
                : (float)$value;
        } elseif (0 === strpos($value, '[') && substr($value, -1) === ']') {
            return explode(',', substr($value, 1, -1));
        }

        return $value;
    }

    protected function resolveEntityClass(?string $entityName, string $version, RequestType $requestType): ?string
    {
        if (!$entityName) {
            return null;
        }

        $entityClass = $entityName;
        if (false === strpos($entityClass, '\\')) {
            $entityClass = ValueNormalizerUtil::convertToEntityClass(
                $this->valueNormalizer,
                $entityName,
                $requestType
            );
        }

        if (!$this->resourcesProvider->isResourceKnown($entityClass, $version, $requestType)) {
            throw new \RuntimeException(
                sprintf('The "%s" entity is not configured to be used in API.', $entityClass)
            );
        }

        return $entityClass;
    }
}
