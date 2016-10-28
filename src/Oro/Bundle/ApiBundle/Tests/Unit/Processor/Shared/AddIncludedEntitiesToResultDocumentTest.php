<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\AddIncludedEntitiesToResultDocument;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class AddIncludedEntitiesToResultDocumentTest extends FormProcessorTestCase
{
    /** @var AddIncludedEntitiesToResultDocument */
    protected $processor;

    public function setUp()
    {
        parent::setUp();
        $this->processor = new AddIncludedEntitiesToResultDocument();
    }

    public function testProcessWithoutIncludedData()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWithoutIncludedEntities()
    {
        $this->context->setIncludedData(['key' => 'value']);
        $this->processor->process($this->context);
    }

    public function testProcessWithoutResponseDocumentBuilder()
    {
        $this->context->setIncludedData(['key' => 'value']);
        $this->context->setIncludedEntities(new IncludedEntityCollection());
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $documentBuilder = $this->getMock(DocumentBuilderInterface::class);
        $includedEntities = new IncludedEntityCollection();

        $normalizedData = ['normalizedKey' => 'normalizedValue'];
        $metadata = new EntityMetadata();
        $entityData = new IncludedEntityData('/included/0', 0);
        $entityData->setNormalizedData($normalizedData);
        $entityData->setMetadata($metadata);
        $includedEntities->add(new \stdClass(), 'Test\Class', 'testId', $entityData);

        $documentBuilder->expects(self::once())
            ->method('addIncludedObject')
            ->with(self::identicalTo($normalizedData), self::identicalTo($metadata));

        $this->context->setIncludedData(['key' => 'value']);
        $this->context->setIncludedEntities($includedEntities);
        $this->context->setResponseDocumentBuilder($documentBuilder);
        $this->processor->process($this->context);
    }
}
