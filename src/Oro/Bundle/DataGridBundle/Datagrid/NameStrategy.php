<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;

class NameStrategy implements NameStrategyInterface
{
    const DELIMITER = ':';

    /**
     * {@inheritdoc}
     */
    public function getDelimiter()
    {
        return self::DELIMITER;
    }

    /**
     * {@inheritdoc}
     */
    public function parseGridName($fullName)
    {
        $parts = $this->parseGridNameAndScope($fullName);
        return $parts[0];
    }

    /**
     * {@inheritdoc}
     */
    public function parseGridScope($fullName)
    {
        $parts = $this->parseGridNameAndScope($fullName);
        return $parts[1];
    }

    /**
     * {@inheritdoc}
     */
    public function buildGridFullName($name, $scope)
    {
        $result = $name;

        if ($scope) {
            $result .= $this->getDelimiter() . $scope;
        }

        $this->parseGridNameAndScope($result); // validate result

        return $result;
    }

    /**
     * @param string $name
     * @return array
     * @throws InvalidArgumentException
     */
    protected function parseGridNameAndScope($name)
    {
        if (substr_count($name, self::DELIMITER) > 1) {
            throw new InvalidArgumentException(
                sprintf(
                    'Grid name "%s" is invalid, it should not contain more than one occurrence of "%s".',
                    $name,
                    self::DELIMITER
                )
            );
        }
        $result = array_pad(explode(self::DELIMITER, $name, 2), 2, '');

        if (!$result[0]) {
            throw new InvalidArgumentException(
                sprintf(
                    'Grid name "%s" is invalid, name must be not empty.',
                    $name
                )
            );
        }

        return $result;
    }
}
