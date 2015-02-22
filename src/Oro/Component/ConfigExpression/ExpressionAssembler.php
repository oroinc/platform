<?php

namespace Oro\Component\ConfigExpression;

class ExpressionAssembler extends AbstractAssembler
{
    const PARAMETERS_KEY = 'parameters';
    const MESSAGE_KEY = 'message';

    /** @var ExpressionFactoryInterface */
    protected $factory;

    /**
     * @param ExpressionFactoryInterface $factory
     */
    public function __construct(ExpressionFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function assemble(array $configuration)
    {
        if (!$this->isExpression($configuration)) {
            return null;
        }

        $options = [];
        $message = null;
        $params  = $this->getEntityParameters($configuration);
        if ($params !== null) {
            if (is_array($params)) {
                $message = $this->extractMessage($params);
                if (isset($params[self::PARAMETERS_KEY]) || array_key_exists(self::PARAMETERS_KEY, $params)) {
                    $params = $params[self::PARAMETERS_KEY];
                    if ($params !== null) {
                        if (is_array($params)) {
                            $options = $this->resolveOptions($params);
                        } else {
                            $options[] = $this->resolveOption($params);
                        }
                    }
                } else {
                    $options = $this->resolveOptions($params);
                }
            } else {
                $options[] = $this->resolveOption($params);
            }
        }

        $expr = $this->factory->create(
            $this->getExpressionType($this->getEntityType($configuration)),
            $this->passConfiguration($options)
        );
        if ($message) {
            $expr->setMessage($message);
        }

        return $expr;
    }

    /**
     * @param array $params
     *
     * @return string|null
     */
    protected function extractMessage(array &$params)
    {
        if (isset($params[self::MESSAGE_KEY])) {
            $message = $params[self::MESSAGE_KEY];
            unset($params[self::MESSAGE_KEY]);
        } elseif (isset($params[self::PARAMETERS_KEY][self::MESSAGE_KEY])) {
            $message = $params[self::PARAMETERS_KEY][self::MESSAGE_KEY];
            unset($params[self::PARAMETERS_KEY][self::MESSAGE_KEY]);
        } else {
            $message = null;
        }

        return $message;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    protected function resolveOptions(array $params)
    {
        $options = [];
        foreach ($params as $key => $value) {
            $options[$key] = $this->resolveOption($value);
        }

        return $options;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    protected function resolveOption($value)
    {
        if ($this->isExpression($value)) {
            return $this->assemble($value);
        } else {
            return $value;
        }
    }
}
