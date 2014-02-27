<?php

namespace Oro\Bundle\FilterBundle\Expression\Date;

use Carbon\Carbon;

class Parser
{
    /**
     * @param Token[] $tokens
     *
     * @return \Carbon\Carbon
     */
    public function parse($tokens)
    {
        $RPNTokens = $this->convertExprToRPN($tokens);

        $stack = [];
        foreach ($RPNTokens as $token) {
            if ($token instanceof Token && $token->getType() === Token::TYPE_OPERATOR) {
                switch ($token->getValue()) {
                    case '+':
                        $result = $this->getValue(array_shift($stack)) + $this->getValue(array_shift($stack));
                        break;
                    case '-':
                        $result = $this->getValue(array_shift($stack)) - $this->getValue(array_shift($stack));
                        break;

                }
                array_push($stack, $result);
            } else {
                $stack[] = $token;
            }
        }

        if (count($stack) > 1) {
            throw new \LogicException('Invalid operator count');
        }
    }

    protected function getValue($data)
    {
        if ($data instanceof Token) {
            return $data->getValue();
        } else {
            return $data;
        }
    }

    /**
     * @param Token[] $tokens
     *
     * @return Token[]
     * @throws \LogicException
     */
    protected function convertExprToRPN($tokens)
    {
        $result = $stack = [];

        foreach ($tokens as $token) {
            if ($token->getValue() === '(') {
                $stack[] = $token;
            } elseif ($token->getValue() === ')') {
                $stackItem = array_pop($stack);
                while ($stackItem !== null && $stackItem->getValue() !== '(') {
                    $result [] = $stackItem;
                    $stackItem = array_pop($stack);
                }
                if (null === $stackItem) {
                    throw new \LogicException('The open parenthesis is missing.');
                }
            } else {
                if ($token->getType() === Token::TYPE_OPERATOR) {
                    $stackItem = array_pop($stack);
                    while ($stackItem !== null) {
                        if ($stackItem->getValue() === '(') {
                            $stack[] = $stackItem;
                            break;
                        } else {
                            $result [] = $stackItem;
                            $stackItem = array_pop($stack);
                        }
                    }
                }
                $stack[] = $token;
            }
        }

        $stackItem = array_pop($stack);
        while (null !== $stackItem) {
            if ($stackItem->getValue() === '(') {
                throw new \LogicException('The close parenthesis is missing.');
            }
            $result [] = $stackItem;
            $stackItem = array_pop($stack);
        }

        return $result;
    }
}
