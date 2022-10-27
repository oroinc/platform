<?php

namespace Oro\Component\ExpressionLanguage;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as SymfonyExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

/**
 * Copy of {@see \Symfony\Component\ExpressionLanguage\ExpressionLanguage} with the following changes:
 * 1 Makes use of custom {@see Parser} and {@see Lexer} classes.
 *
 * Version of the "symfony/expression-language" component used at the moment of customization: 5.3.7
 * {@see https://github.com/symfony/expression-language/blob/v5.3.7/ExpressionLanguage.php}
 */
class ExpressionLanguage extends SymfonyExpressionLanguage
{
    protected CacheItemPoolInterface $cache;

    protected ?Lexer $lexer = null;

    protected ?Parser $parser = null;

    public function __construct(CacheItemPoolInterface $cache = null, array $providers = [])
    {
        $this->cache = $cache ?? new ArrayAdapter();
        parent::__construct($cache, $providers);
    }

    /**
     * {@inheritdoc}
     *
     * Copy of {@see \Symfony\Component\ExpressionLanguage\ExpressionLanguage::parse()}:
     * - makes use own $parser and $cache properties.
     */
    public function parse($expression, array $names)
    {
        if ($expression instanceof ParsedExpression) {
            return $expression;
        }

        asort($names);
        $cacheKeyItems = [];

        foreach ($names as $nameKey => $name) {
            $cacheKeyItems[] = \is_int($nameKey) ? $name : $nameKey . ':' . $name;
        }

        $cacheItem = $this->cache->getItem(rawurlencode($expression.'//'.implode('|', $cacheKeyItems)));

        if (null === $parsedExpression = $cacheItem->get()) {
            $nodes = $this->getParser()->parse($this->getLexer()->tokenize((string)$expression), $names);
            $parsedExpression = new ParsedExpression((string)$expression, $nodes);

            $cacheItem->set($parsedExpression);
            $this->cache->save($cacheItem);
        }

        return $parsedExpression;
    }

    /**
     * {@inheritdoc}
     *
     * Copy of {@see \Symfony\Component\ExpressionLanguage\ExpressionLanguage::lint()}:
     * - makes use own $parser and $lexer properties.
     */
    public function lint($expression, ?array $names): void
    {
        if ($expression instanceof ParsedExpression) {
            return;
        }

        $this->getParser()->lint($this->getLexer()->tokenize((string)$expression), $names);
    }

    protected function getLexer(): Lexer
    {
        if (null === $this->lexer) {
            $this->lexer = new Lexer();
        }

        return $this->lexer;
    }

    protected function getParser(): Parser
    {
        if (null === $this->parser) {
            $this->parser = new Parser($this->functions);
        }

        return $this->parser;
    }
}
