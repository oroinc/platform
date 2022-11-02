<?php

namespace Oro\Component\ExpressionLanguage\Node;

use Symfony\Component\ExpressionLanguage\Compiler;
use Symfony\Component\ExpressionLanguage\Node\Node;

/**
 *
 * Copy of {@see \Symfony\Component\ExpressionLanguage\Node\BinaryNode} with following changes:
 * 1 Aliases "=", "!=" operators to "===" and "!==" correspondingly before compiling or evaluating.
 * 2 Enables strict comparison for "in", "not in" operators.
 *
 * Custom lines are located between comments [CUSTOM LINES]...[/CUSTOM LINES]
 *
 * Version of the "symfony/expression-language" component used at the moment of customization: 5.3.7
 * {@see https://github.com/symfony/expression-language/blob/v5.3.7/Node/BinaryNode.php}
 */
class BinaryNode extends Node
{
    private const OPERATORS = [
        '~' => '.',
        'and' => '&&',
        'or' => '||',
    ];

    private const FUNCTIONS = [
        '**' => 'pow',
        '..' => 'range',
        'in' => 'in_array',
        'not in' => '!in_array',
    ];

    public function __construct(string $operator, Node $left, Node $right)
    {
        parent::__construct(
            ['left' => $left, 'right' => $right],
            ['operator' => $operator]
        );
    }

    public function compile(Compiler $compiler)
    {
        // [CUSTOM LINES]
        $operator = $this->getOperator();
        // [/CUSTOM LINES]

        if ('matches' == $operator) {
            $compiler
                ->raw('preg_match(')
                ->compile($this->nodes['right'])
                ->raw(', ')
                ->compile($this->nodes['left'])
                ->raw(')')
            ;

            return;
        }

        if (isset(self::FUNCTIONS[$operator])) {
            $compiler
                ->raw(sprintf('%s(', self::FUNCTIONS[$operator]))
                ->compile($this->nodes['left'])
                ->raw(', ')
                ->compile($this->nodes['right'])
                ->raw(')')
            ;

            return;
        }

        if (isset(self::OPERATORS[$operator])) {
            $operator = self::OPERATORS[$operator];
        }

        $compiler
            ->raw('(')
            ->compile($this->nodes['left'])
            ->raw(' ')
            ->raw($operator)
            ->raw(' ')
            ->compile($this->nodes['right'])
            ->raw(')')
        ;
    }

    public function evaluate(array $functions, array $values)
    {
        // [CUSTOM LINES]
        $operator = $this->attributes['operator'];
        if (in_array($operator, ['=', '!=', 'not in', 'in'])) {
            $result = $this->customEvaluate($operator, $functions, $values);
        } else {
            $result = $this->symfonyEvaluate($functions, $values);
        }

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function symfonyEvaluate(array $functions, array $values)
    {
        // [/CUSTOM LINES]
        $operator = $this->attributes['operator'];
        $left = $this->nodes['left']->evaluate($functions, $values);

        if (isset(self::FUNCTIONS[$operator])) {
            $right = $this->nodes['right']->evaluate($functions, $values);

            if ('not in' === $operator) {
                return !\in_array($left, $right);
            }
            $f = self::FUNCTIONS[$operator];

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
            case '==':
                return $left == $right;
            case '===':
                return $left === $right;
            case '!=':
                return $left != $right;
            case '!==':
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
                return !\in_array($left, $right);
            case 'in':
                return \in_array($left, $right);
            case '+':
                return $left + $right;
            case '-':
                return $left - $right;
            case '~':
                return $left.$right;
            case '*':
                return $left * $right;
            case '/':
                if (0 == $right) {
                    throw new \DivisionByZeroError('Division by zero.');
                }

                return $left / $right;
            case '%':
                if (0 == $right) {
                    throw new \DivisionByZeroError('Modulo by zero.');
                }

                return $left % $right;
            case 'matches':
                return preg_match($right, $left);
        }
    }

    public function toArray()
    {
        return ['(', $this->nodes['left'], ' '.$this->attributes['operator'].' ', $this->nodes['right'], ')'];
    }

    // [CUSTOM LINES]
    private function getOperator(): string
    {
        $operatorsMap = [
            '=' => '===',
            '!=' => '!==',
        ];

        $operator = $this->attributes['operator'];

        return $operatorsMap[$operator] ?? $operator;
    }

    /**
     * Aliases "=", "!=" operators to "===" and "!==" correspondingly.
     * Enables strict comparison for "in", "not in" operators.
     */
    private function customEvaluate(string $operator, array $functions, array $values): bool
    {
        $left = $this->nodes['left']->evaluate($functions, $values);
        $right = $this->nodes['right']->evaluate($functions, $values);

        return match ($operator) {
            '=' => $left === $right,
            '!=' => $left !== $right,
            'not in' => !\in_array($left, $right, true),
            'in' => \in_array($left, $right, true),
        };
    }
    // [/CUSTOM LINES]
}
