<?php

namespace Oro\Component\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as SymfonyExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\ParserCache\ArrayParserCache;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;

class ExpressionLanguage extends SymfonyExpressionLanguage
{
    /**
     * @var ParserCacheInterface
     */
    protected $cache;

    /**
     * @var Lexer
     */
    protected $lexer;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @param ParserCacheInterface|null $cache
     * @param array $providers
     */
    public function __construct(ParserCacheInterface $cache = null, array $providers = [])
    {
        $this->cache = $cache ?: new ArrayParserCache();
        parent::__construct($cache, $providers);
    }

    /**
     * Copy of Symfony\Component\ExpressionLanguage\ExpressionLanguage::parse
     *
     * @param Expression|string $expression The expression to parse
     * @param array $names An array of valid names
     *
     * @return ParsedExpression A ParsedExpression instance
     */
    public function parse($expression, $names)
    {
        if ($expression instanceof ParsedExpression) {
            return $expression;
        }

        asort($names);
        $cacheKeyItems = [];

        foreach ($names as $nameKey => $name) {
            $cacheKeyItems[] = is_int($nameKey) ? $name : $nameKey.':'.$name;
        }

        $key = $expression.'//'.implode('|', $cacheKeyItems);

        if (null === $parsedExpression = $this->cache->fetch($key)) {
            $nodes = $this->getParser()->parse($this->getLexer()->tokenize((string)$expression), $names);
            $parsedExpression = new ParsedExpression((string)$expression, $nodes);

            $this->cache->save($key, $parsedExpression);
        }

        return $parsedExpression;
    }

    /**
     * @return Lexer
     */
    protected function getLexer()
    {
        if (null === $this->lexer) {
            $this->lexer = new Lexer();
        }

        return $this->lexer;
    }

    /**
     * @return Parser
     */
    protected function getParser()
    {
        if (null === $this->parser) {
            $this->parser = new Parser($this->functions);
        }

        return $this->parser;
    }
}
