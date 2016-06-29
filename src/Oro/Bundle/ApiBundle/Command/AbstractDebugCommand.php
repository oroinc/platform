<?php

namespace Oro\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

abstract class AbstractDebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption(
                'request-type',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'The request type. Use <comment>"any"</comment> to ignore the request type.',
                $this->getDefaultRequestType()
            );
    }

    /**
     * @return string[]
     */
    protected function getDefaultRequestType()
    {
        return [RequestType::REST, RequestType::JSON_API];
    }

    /**
     * @param InputInterface $input
     *
     * @return RequestType
     */
    protected function getRequestType(InputInterface $input)
    {
        $value = $input->getOption('request-type');
        if (count($value) === 1 && 'any' === $value[0]) {
            $value = [];
        }

        return new RequestType($value);
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function convertValueToString($value)
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

    /**
     * @param string|null $entityName
     * @param RequestType $requestType
     *
     * @return string
     */
    protected function resolveEntityClass($entityName, RequestType $requestType)
    {
        if ($entityName && false !== strpos($entityName, '\\')) {
            return $entityName;
        }

        return ValueNormalizerUtil::convertToEntityClass(
            $this->getContainer()->get('oro_api.value_normalizer'),
            $entityName,
            $requestType
        );
    }
}
