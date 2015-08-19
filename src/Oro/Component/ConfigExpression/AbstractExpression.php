<?php

namespace Oro\Component\ConfigExpression;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

abstract class AbstractExpression implements ExpressionInterface
{
    /** @var string */
    private $message;

    /** @var \ArrayAccess|null */
    protected $errors;

    /**
     * {@inheritdoc}
     */
    public function evaluate($context, \ArrayAccess $errors = null)
    {
        $this->errors = $errors;
        $result       = $this->doEvaluate($context);
        $this->errors = null;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Evaluates the expression.
     *
     * @param mixed $context
     *
     * @return mixed
     */
    abstract protected function doEvaluate($context);

    /**
     * Gets the condition error message.
     *
     * @return string|null
     */
    protected function getMessage()
    {
        return $this->message;
    }

    /**
     * Registers the expression failure if the errors collection is provided in evaluate method.
     *
     * @param mixed       $context The evaluation context
     * @param string|null $reason  The failure reason
     */
    protected function addError($context, $reason = null)
    {
        if ($this->errors !== null) {
            $message = $this->getMessage();
            if ($message) {
                $params = $this->getMessageParameters($context);
                if ($reason !== null) {
                    $params['{{ reason }}'] = $reason;
                }
                $this->errors[] = ['message' => $message, 'parameters' => $params];
            }
        }
    }

    /**
     * @param mixed $context
     *
     * @return array
     */
    protected function getMessageParameters($context)
    {
        return [];
    }

    /**
     * @param mixed $params
     *
     * @return array
     */
    protected function convertToArray($params)
    {
        $result  = null;
        $message = $this->getMessage();
        if ($message !== null) {
            $result['message'] = $message;
        }
        if ($params !== null) {
            if (!is_array($params)) {
                $params = [$params];
            }
            foreach ($params as $param) {
                if ($param instanceof PropertyPathInterface) {
                    $result['parameters'][] = '$' . (string)$param;
                } elseif ($param instanceof ExpressionInterface) {
                    $result['parameters'][] = $param->toArray();
                } else {
                    $result['parameters'][] = $param;
                }
            }
        }

        return ['@' . $this->getName() => $result];
    }

    /**
     * @param mixed  $params
     * @param string $factoryAccessor
     *
     * @return array
     */
    protected function convertToPhpCode($params, $factoryAccessor)
    {
        $compiledParams = [];
        if ($params !== null) {
            if (!is_array($params)) {
                $params = [$params];
            }
            foreach ($params as $param) {
                if (null === $param) {
                    $compiledParams[] = 'null';
                } elseif ($param instanceof PropertyPathInterface) {
                    $compiledParams[] =
                        'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\''
                        . str_replace('\'', '\\\'', (string)$param)
                        . '\', ['
                        . $this->compilePathElements($param)
                        . '], ['
                        . $this->compilePathIndexes($param)
                        . '])';
                } elseif ($param instanceof ExpressionInterface) {
                    $compiledParams[] = $param->compile($factoryAccessor);
                } elseif (is_string($param)) {
                    $compiledParams[] = '\'' . str_replace('\'', '\\\'', $param) . '\'';
                } elseif (is_bool($param)) {
                    $compiledParams[] = $param ? 'true' : 'false';
                } else {
                    $compiledParams[] = (string)$param;
                }
            }
        }


        $compiled =
            $factoryAccessor
            . '->create(\''
            . $this->getName()
            . '\', ['
            . implode(', ', $compiledParams)
            . '])';
        $message  = $this->getMessage();
        if ($message !== null) {
            $compiled .= '->setMessage(\'' . str_replace('\'', '\\\'', $message) . '\')';
        }

        return $compiled;
    }

    /**
     * @param PropertyPathInterface $propertyPath
     *
     * @return string
     */
    protected function compilePathElements(PropertyPathInterface $propertyPath)
    {
        return implode(
            ', ',
            array_map(
                function ($val) {
                    return '\'' . $val . '\'';
                },
                $propertyPath->getElements()
            )
        );
    }

    /**
     * @param PropertyPathInterface $propertyPath
     *
     * @return string
     */
    protected function compilePathIndexes(PropertyPathInterface $propertyPath)
    {
        $indexes = [];
        $count   = count($propertyPath->getElements());
        for ($i = 0; $i < $count; $i++) {
            $indexes[] = $propertyPath->isIndex($i);
        }

        return implode(
            ', ',
            array_map(
                function ($val) {
                    return $val ? 'true' : 'false';
                },
                $indexes
            )
        );
    }
}
