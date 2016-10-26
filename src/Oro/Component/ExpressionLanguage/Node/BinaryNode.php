<?php

namespace Oro\Component\ExpressionLanguage\Node;

use Symfony\Component\ExpressionLanguage\Compiler;
use Symfony\Component\ExpressionLanguage\Node\BinaryNode as SymfonyBinaryNode;

class BinaryNode extends SymfonyBinaryNode
{
    /**
     * @var array
     */
    protected static $functions = [
        '**' => 'pow',
        '..' => 'range',
        'in' => 'in_array',
        'not in' => '!in_array',
    ];

    /**
     * {@inheritdoc}
     */
    public function compile(Compiler $compiler)
    {
        $operator = $this->attributes['operator'];

        $equalSigns = [
            '=' => '===',
            '!=' => '!==',
        ];

        if (array_key_exists($operator, $equalSigns)) {
            $compiler
                ->raw('(')
                ->compile($this->nodes['left'])
                ->raw(' ')
                ->raw($equalSigns[$operator])
                ->raw(' ')
                ->compile($this->nodes['right'])
                ->raw(')');
        } else {
            parent::compile($compiler);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * Copy of \Symfony\Component\ExpressionLanguage\Node\BinaryNode::evaluate with "=" and without "==", "===", "!=="
     *
     * {@inheritdoc}
     */
    public function evaluate($functions, $values)
    {
        $operator = $this->attributes['operator'];
        $left = $this->nodes['left']->evaluate($functions, $values);

        if (isset(static::$functions[$operator])) {
            $right = $this->nodes['right']->evaluate($functions, $values);

            if ('not in' === $operator) {
                return !in_array($left, $right, true);
            }
            $f = static::$functions[$operator];

            return $f($left, $right);
        }

        switch ($operator) {
            case 'or':
            case '||':
                return $left || $this->nodes['right']->evaluate($functions, $values);
            case 'and':
            case '&&':
                return $left && $this->nodes['right']->evaluate($functions, $values);
        }

        $right = $this->nodes['right']->evaluate($functions, $values);

        switch ($operator) {
            case '|':
                return $left | $right;
            case '^':
                return $left ^ $right;
            case '&':
                return $left & $right;
            case '=':
                return $left === $right;
            case '!=':
                return $left !== $right;
            case '<':
                return $left < $right;
            case '>':
                return $left > $right;
            case '>=':
                return $left >= $right;
            case '<=':
                return $left <= $right;
            case 'not in':
                return !in_array($left, $right, true);
            case 'in':
                return in_array($left, $right, true);
            case '+':
                return $left + $right;
            case '-':
                return $left - $right;
            case '~':
                return $left.$right;
            case '*':
                return $left * $right;
            case '/':
                return $left / $right;
            case '%':
                return $left % $right;
            case 'matches':
                return preg_match($right, $left);
        }
    }
}
