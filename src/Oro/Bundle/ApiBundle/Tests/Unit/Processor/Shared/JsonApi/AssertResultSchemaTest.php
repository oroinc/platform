<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\AssertResultSchema;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class AssertResultSchemaTest extends GetListProcessorTestCase
{
    /** @var AssertResultSchema */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new AssertResultSchema();
    }

    public function testProcessWhenResultDoesNotExist()
    {
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The result must be an array.
     */
    public function testProcessWhenResultIsNotArray()
    {
        $this->context->setResult(null);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The result must contain at least one of the following sections: data, errors, meta.
     */
    public function testProcessWhenResultIsEmptyArray()
    {
        $this->context->setResult([]);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The result must contain at least one of the following sections: data, errors, meta.
     */
    public function testProcessWhenResultDoesNotContainAnyRequiresSection()
    {
        $this->context->setResult(['optional_section' => []]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenResultContainsDataSection()
    {
        $this->context->setResult([JsonApiDoc::DATA => []]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenResultContainsErrorsSection()
    {
        $this->context->setResult([JsonApiDoc::ERRORS => []]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenResultContainsMetaSection()
    {
        $this->context->setResult([JsonApiDoc::META => []]);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The sections "data" and "errors" must not coexist in the result.
     */
    public function testProcessWhenResultContainsBothDataAndErrorsSections()
    {
        $this->context->setResult([JsonApiDoc::DATA => [], JsonApiDoc::ERRORS => []]);
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The result can contain the "included" section only together with the "data" section.
     */
    public function testProcessWhenResultContainsIncludedSectionButDoesNotContainDataSection()
    {
        $this->context->setResult([JsonApiDoc::INCLUDED => [], JsonApiDoc::META => []]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenResultContainsDataAndIncludedSections()
    {
        $this->context->setResult([JsonApiDoc::DATA => [], JsonApiDoc::INCLUDED => []]);
        $this->processor->process($this->context);
    }
}
