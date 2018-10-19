<?php

namespace Oro\Component\ExpressionLanguage;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as SymfonyExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class ExpressionLanguage extends SymfonyExpressionLanguage
{
    /**
     * @var CacheItemPoolInterface
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
     * @param CacheItemPoolInterface|null $cache
     * @param array $providers
     */
    public function __construct(CacheItemPoolInterface $cache = null, array $providers = [])
    {
        $this->cache = $cache ?: new ArrayAdapter();
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

        $key = rawurlencode($expression.'//'.implode('|', $cacheKeyItems));

        $cacheItem = $this->cache->getItem($key);
        if (null === $parsedExpression = $cacheItem->get()) {
            $nodes = $this->getParser()->parse($this->getLexer()->tokenize((string)$expression), $names);
            $parsedExpression = new ParsedExpression((string)$expression, $nodes);

            $cacheItem->set($parsedExpression);
            $this->cache->save($cacheItem);
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
