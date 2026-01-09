<?php

namespace Oro\Bundle\SearchBundle\Api\Processor\MultiTargetSearch;

use Oro\Bundle\ApiBundle\Exception\InvalidFilterException;
use Oro\Bundle\SearchBundle\Query\Expression\Lexer;
use Oro\Bundle\SearchBundle\Query\Expression\Token;
use Oro\Bundle\SearchBundle\Query\Expression\TokenStream;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;

/**
 * Analyzes the search API resource search expression and converts it to a stream of tokens.
 */
class MultiTargetSearchExpressionLexer
{
    public function __construct(
        private readonly array $searchFieldMappings,
        private readonly array $fieldMappings
    ) {
    }

    public function tokenize(string $searchExpression): TokenStream
    {
        $stream = (new Lexer())->tokenize($searchExpression);

        $tokens = [];
        $comparison = [];
        while (!$stream->isEOF()) {
            /** @var Token $token */
            $token = $stream->current;
            if ($comparison) {
                if ($token->test(Token::OPERATOR_TYPE) && $this->hasToken($comparison, Token::OPERATOR_TYPE)) {
                    $this->addComparisonTokens($tokens, $comparison);
                    $tokens[] = $token;
                    $comparison = [];
                } elseif ($token->test(Token::PUNCTUATION_TYPE, ')')) {
                    if ($this->hasToken($comparison, Token::PUNCTUATION_TYPE, '(')) {
                        $comparison[] = $token;
                        $this->addComparisonTokens($tokens, $comparison);
                    } else {
                        $this->addComparisonTokens($tokens, $comparison);
                        $tokens[] = $token;
                    }
                    $comparison = [];
                } else {
                    $comparison[] = $token;
                }
            } elseif ($token->test(Token::STRING_TYPE)) {
                $comparison[] = $token;
            } else {
                $tokens[] = $token;
            }
            $stream->next();
        }
        if ($comparison) {
            $this->addComparisonTokens($tokens, $comparison);
        }
        $tokens[] = $stream->current;

        return new TokenStream($tokens);
    }

    private function hasToken(array $tokens, string $type, ?string $value = null): bool
    {
        /** @var Token $token */
        foreach ($tokens as $token) {
            if ($token->test($type, $value)) {
                return true;
            }
        }

        return false;
    }

    private function addComparisonTokens(array &$tokens, array $comparison): void
    {
        /** @var Token $fieldNameToken */
        $fieldNameToken = array_shift($comparison);
        if (
            isset($this->fieldMappings[$fieldNameToken->value])
            && \is_array($this->fieldMappings[$fieldNameToken->value])
        ) {
            $tokens[] = new Token(Token::PUNCTUATION_TYPE, '(', 0);
            $count = 0;
            foreach ($this->fieldMappings[$fieldNameToken->value] as $searchFieldName) {
                if ($count > 0) {
                    $tokens[] = new Token(Token::OPERATOR_TYPE, 'or', 0);
                }
                $this->addComparisonToken($tokens, $searchFieldName, $fieldNameToken, $comparison);
                $count++;
            }
            $tokens[] = new Token(Token::PUNCTUATION_TYPE, ')', 0);
        } else {
            $searchFieldName = $this->fieldMappings[$fieldNameToken->value] ?? $fieldNameToken->value;
            if (!isset($this->searchFieldMappings[$searchFieldName])) {
                throw new InvalidFilterException(\sprintf(
                    'The field "%s" is not supported.',
                    $fieldNameToken->value
                ));
            }
            $this->addComparisonToken($tokens, $searchFieldName, $fieldNameToken, $comparison);
        }
    }

    private function addComparisonToken(
        array &$tokens,
        string $searchFieldName,
        Token $fieldNameToken,
        array $restOfTokens
    ): void {
        $fieldTypeMapping = $this->searchFieldMappings[$searchFieldName];
        $fieldType = $fieldTypeMapping ? $fieldTypeMapping['type'] : SearchQuery::TYPE_TEXT;
        $tokens[] = new Token(Token::STRING_TYPE, $fieldType, 0);
        $tokens[] = new Token(Token::STRING_TYPE, $searchFieldName, $fieldNameToken->cursor);
        foreach ($restOfTokens as $token) {
            $tokens[] = $token;
        }
    }
}
