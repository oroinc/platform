<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ParameterBinderInterface;
use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;

/**
 * Binds parameters from datagrid to it's datasource query
 */
class ParameterBinder implements ParameterBinderInterface
{
    /**
     * Binds datagrid parameters to datasource query.
     *
     * Example of usage:
     * <code>
     *  // get parameter "name" from datagrid parameter bag and add it to datasource query
     *  $queryParameterBinder->bindParameters($datagrid, ['name']);
     *
     *  // get parameter "id" from datagrid parameter bag and add it to datasource query as parameter "client_id"
     *  $queryParameterBinder->bindParameters($datagrid, ['client_id' => 'id']);
     *
     *  // get parameter "email" from datagrid parameter bag and add it to datasource query, all other existing query
     *  // parameters will be cleared
     *  $queryParameterBinder->bindParameters($datagrid, ['email'], false);
     *
     *  // get parameter with path "_parameters.data_in" from datagrid parameter
     *  // and add it to datasource query as parameter "data_in"
     *  $queryParameterBinder->bindParameters($datagrid, ['data_in' => '_parameters.data_in']);
     *
     *  // get parameter with path "_parameters.data_in" from datagrid parameter
     *  // and add it to datasource query as parameter "data_in",
     *  // if parameter is not exist, set default value - empty array,
     *  // and do the same for data_not_in
     *  $queryParameterBinder->bindParameters(
     *      $datagrid,
     *      [
     *          'data_in' => [
     *              'path' => '_parameters.data_in',
     *              'default' => [],
     *          ],
     *          [
     *              'name' => 'data_not_in'
     *              'path' => '_parameters.data_not_in',
     *              'default' => [],
     *          ]
     *      ]
     *  );
     * </code>
     *
     * @param DatagridInterface $datagrid
     * @param array $datasourceToDatagridParameters
     * @param bool $append
     * @throws InvalidArgumentException When datasource of grid is not ORM
     * @throws NoSuchPropertyException When datagrid has no parameter with specified name or path
     */
    public function bindParameters(
        DatagridInterface $datagrid,
        array $datasourceToDatagridParameters,
        $append = true
    ) {
        if (!$datasourceToDatagridParameters) {
            return;
        }

        $datasource = $datagrid->getDatasource();

        if (!$datasource instanceof OrmDatasource) {
            throw new InvalidArgumentException(
                sprintf(
                    'Datagrid datasource has unexpected type "%s", "%s" is expected.',
                    get_class($datasource),
                    'Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource'
                )
            );
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $datasource->getQueryBuilder();

        $queryParameters = $queryBuilder->getParameters();

        if (!$append) {
            $queryParameters->clear();
        }

        $datagridParameters = $datagrid->getParameters()->all();

        foreach ($datasourceToDatagridParameters as $index => $value) {
            $config = $this->parseArrayParameterConfig($index, $value);

            $value = $this->getParameterValue($datagridParameters, $config);
            $type = isset($config['type']) ? $config['type'] : null;

            $this->addOrReplaceParameter($queryParameters, new Parameter($config['name'], $value, $type));
        }
    }

    /**
     * @param string $index
     * @param string|array $value
     * @return array
     * @throws InvalidArgumentException
     */
    protected function parseArrayParameterConfig($index, $value)
    {
        if (!is_array($value)) {
            if (is_numeric($index)) {
                $config = ['name' => $value, 'path' => $value];
            } else {
                $config = ['name' => $index, 'path' => $value];
            }
        } else {
            $config = $value;
        }

        if (empty($config['name']) && !is_numeric($index)) {
            $config['name'] = $index;
        }

        if (empty($config['name'])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot bind parameter to data source, expected bind parameter format is a string ' .
                    'or array with required "name" key, actual array keys are "%s".',
                    implode('", "', array_keys($config))
                )
            );
        }

        if (empty($config['path'])) {
            $config['path'] = $config['name'];
        }

        return $config;
    }

    /**
     * @param array $source
     * @param array $config
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function getParameterValue($source, array $config)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        try {
            $path = '';
            foreach (explode('.', $config['path']) as $part) {
                $path .= '[' . $part . ']';
            }
            $result = $propertyAccessor->getValue($source, $path);
        } catch (NoSuchPropertyException $exception) {
            if (isset($config['default'])) {
                $result = $config['default'];
            } else {
                throw new InvalidArgumentException(
                    sprintf(
                        'Cannot bind datasource parameter "%s", there is no datagrid parameter with path "%s".',
                        $config['name'],
                        $config['path']
                    ),
                    0,
                    $exception
                );
            }
        }
        if ((null === $result || $result === [] || $result === ['']) && isset($config['default'])) {
            $result = $config['default'];
        }
        return $result;
    }

    /**
     * Adds parameter to collection and removes all other parameters with same name.
     *
     * @param ArrayCollection $parameters
     * @param Parameter $newParameter
     */
    protected function addOrReplaceParameter(ArrayCollection $parameters, Parameter $newParameter)
    {
        $removeParameters = [];

        /** @var Parameter $parameter */
        foreach ($parameters->getValues() as $parameter) {
            if ($parameter->getName() === $newParameter->getName()) {
                $removeParameters[] = $parameter;
            }
        }

        foreach ($removeParameters as $removeParameter) {
            $parameters->removeElement($removeParameter);
        }

        $parameters->add($newParameter);
    }
}
