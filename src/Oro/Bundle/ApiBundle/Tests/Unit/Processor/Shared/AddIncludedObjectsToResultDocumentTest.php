<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\IncludedObjectCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedObjectData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\AddIncludedObjectsToResultDocument;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class AddIncludedObjectsToResultDocumentTest extends FormProcessorTestCase
{
    /** @var AddIncludedObjectsToResultDocument */
    protected $processor;

    public function setUp()
    {
        parent::setUp();
        $this->processor = new AddIncludedObjectsToResultDocument();
    }

    public function testProcessWithoutIncludedData()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWithoutIncludedObjects()
    {
        $this->context->setIncludedData(['key' => 'value']);
        $this->processor->process($this->context);
    }

    public function testProcessWithoutResponseDocumentBuilder()
    {
        $this->context->setIncludedData(['key' => 'value']);
        $this->context->setIncludedObjects(new IncludedObjectCollection());
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $documentBuilder = $this->getMock(DocumentBuilderInterface::class);
        $includedObjects = new IncludedObjectCollection();

        $normalizedData = ['normalizedKey' => 'normalizedValue'];
        $metadata = new EntityMetadata();
        $objectData = new IncludedObjectData('/included/0', 0);
        $objectData->setNormalizedData($normalizedData);
        $objectData->setMetadata($metadata);
        $includedObjects->add(new \stdClass(), 'Test\Class', 'testId', $objectData);

        $documentBuilder->expects(self::once())
            ->method('addIncludedObject')
            ->with(self::identicalTo($normalizedData), self::identicalTo($metadata));

        $this->context->setIncludedData(['key' => 'value']);
        $this->context->setIncludedObjects($includedObjects);
        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->processor->process($this->context);
    }
}
