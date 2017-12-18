<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Engine\TextFilteredObjectMapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

class TextFilteredObjectMapperTest extends ObjectMapperTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->mapper = new TextFilteredObjectMapper($this->dispatcher, $this->mappingConfig);
        $this->mapper->setMappingProvider($this->mapperProvider);
        $this->mapper->setPropertyAccessor(PropertyAccess::createPropertyAccessor());
        $this->mapper->setHtmlTagHelper($this->htmlTagHelper);
    }

    /**
     * {@inheritdoc}
     */
    protected function clearTextData(array $fields)
    {
        foreach ($fields as $name => &$value) {
            $value = str_replace(['<p>', '</p>'], ['', ''], $value);
        }

        return $fields;
    }
}
