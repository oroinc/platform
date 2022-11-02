<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Nelmio\Alice\IsAServiceTrait;
use Nelmio\Alice\Parser\ChainableParserInterface;

/**
 * Expanded Alice yaml file parsed that clears 'dependencies' and 'initial' parameters.
 */
class AliceYamlParser implements ChainableParserInterface
{
    use IsAServiceTrait;

    /**
     * @var ChainableParserInterface
     */
    protected $yamlParser;

    public function __construct(ChainableParserInterface $yamlParser)
    {
        $this->yamlParser = $yamlParser;
    }

    /**
     * {@inheritdoc}
     */
    public function canParse(string $file): bool
    {
        return $this->yamlParser->canParse($file);
    }

    /**
     * {@inheritDoc}
     */
    public function parse(string $file): array
    {
        $data = $this->yamlParser->parse($file);
        unset($data['dependencies'], $data['initial']);

        return $data;
    }
}
