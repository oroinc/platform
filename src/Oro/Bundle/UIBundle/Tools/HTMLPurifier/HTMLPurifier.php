<?php

namespace Oro\Bundle\UIBundle\Tools\HTMLPurifier;

use Oro\Bundle\TranslationBundle\Translation\TranslatorAwareInterface;
use Oro\Bundle\TranslationBundle\Translation\TranslatorAwareTrait;

/**
 * Facade that coordinates HTML Purifier's subsystems in order to purify HTML.
 */
class HTMLPurifier extends \HTMLPurifier implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * Array of extra filter objects to run on HTML,
     * for backwards compatibility.
     * @var \HTMLPurifier_Filter[]
     */
    private $filters = [];

    /**
     * {@inheritDoc}
     */
    public function purify($html, $config = null): string
    {
        $config = $config ? \HTMLPurifier_Config::create($config) : $this->config;

        // implementation is partially environment dependant, partially
        // configuration dependant
        $lexer = \HTMLPurifier_Lexer::create($config);

        $context = new \HTMLPurifier_Context();

        // setup HTML generator
        $this->generator = new \HTMLPurifier_Generator($config, $context);
        $context->register('Generator', $this->generator);

        // set up global context variables
        if ($config->get('Core.CollectErrors')) {
            $languageFactory = LanguageFactory::instance();
            $languageFactory->setTranslator($this->translator);

            $language = $languageFactory->create($config, $context);
            $context->register('Locale', $language);

            $errorCollector = new ErrorCollector($context);
            $context->register('ErrorCollector', $errorCollector);
        }

        // setup idAccumulator context, necessary due to the fact that
        // AttrValidator can be called from many places
        $idAccumulator = \HTMLPurifier_IDAccumulator::build($config, $context);
        $context->register('IDAccumulator', $idAccumulator);

        $html = \HTMLPurifier_Encoder::convertToUTF8($html, $config, $context);

        // setup filters
        $filters = $this->getFilters($config);

        foreach ($filters as $filter) {
            $html = $filter->preFilter($html, $config, $context);
        }

        // purified HTML
        $html =
            $this->generator->generateFromTokens(
                // list of tokens
                $this->strategy->execute(
                    // list of un-purified tokens
                    $lexer->tokenizeHTML(
                        // un-purified HTML
                        $html,
                        $config,
                        $context
                    ),
                    $config,
                    $context
                )
            );

        $filterSize = count($filters);
        for ($i = $filterSize - 1; $i >= 0; $i--) {
            $html = $filters[$i]->postFilter($html, $config, $context);
        }

        $html = \HTMLPurifier_Encoder::convertFromUTF8($html, $config, $context);
        $this->context =& $context;

        return $html;
    }

    /**
     * @param \HTMLPurifier_Config $config
     * @return \HTMLPurifier_Filter[]
     */
    private function getFilters(\HTMLPurifier_Config $config): array
    {
        $filterFlags = $config->getBatch('Filter');
        $customFilters = $filterFlags['Custom'];
        unset($filterFlags['Custom']);

        $filters = [];
        foreach ($filterFlags as $filter => $flag) {
            if (!$flag) {
                continue;
            }
            if (str_contains($filter, '.')) {
                continue;
            }
            $class = "HTMLPurifier_Filter_$filter";
            $filters[] = new $class;
        }

        foreach ($customFilters as $filter) {
            $filters[] = $filter;
        }

        return array_merge($filters, $this->filters);
    }
}
