<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextStub;
use Oro\Bundle\ApiBundle\Util\UpsertCriteriaBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpsertCriteriaBuilderTest extends TestCase
{
    private ValueNormalizer&MockObject $valueNormalizer;
    private UpsertCriteriaBuilder $upsertCriteriaBuilder;

    #[\Override]
    protected function setUp(): void
    {
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $this->upsertCriteriaBuilder = new UpsertCriteriaBuilder($this->valueNormalizer);
    }

    private function getContext(): FormContext
    {
        return new FormContextStub(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    public function testGetUpsertFindEntityCriteria(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->addField(new FieldMetadata('field1'))->setDataType('integer');
        $metadata->addField(new FieldMetadata('field2'))->setDataType('string');
        $metadata->addField(new FieldMetadata('field3'))->setDataType('string');
        $metadata->addAssociation(new AssociationMetadata('field4'))->setDataType('integer');
        $context = $this->getContext();
        $context->getRequestType()->add('test');

        $this->valueNormalizer->expects(self::exactly(3))
            ->method('normalizeValue')
            ->willReturnMap([
                ['123', 'integer', $context->getRequestType(), false, false, [], 123],
                ['val', 'string', $context->getRequestType(), false, false, [], 'normalized'],
                ['345', 'integer', $context->getRequestType(), false, false, [], 345],
            ]);

        $criteria = $this->upsertCriteriaBuilder->getUpsertFindEntityCriteria(
            $metadata,
            ['field1', 'field2', 'field3', 'field4'],
            ['field1' => '123', 'field2' => 'val', 'field3' => null, 'field4' => ['id' => '345']],
            '/meta/upsert',
            $context
        );
        self::assertFalse($context->hasErrors());
        self::assertSame(
            ['field1' => 123, 'field2' => 'normalized', 'field3' => null, 'field4' => 345],
            $criteria
        );
    }

    public function testGetUpsertFindEntityCriteriaWhenSomeRequestedFieldAreInvalid(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->addField(new FieldMetadata('field1'))->setDataType('string');
        $toManyAssociation = $metadata->addAssociation(new AssociationMetadata('field2'));
        $toManyAssociation->setDataType('integer');
        $toManyAssociation->setIsCollection(true);
        $metadata->addField(new FieldMetadata('field3'))->setDataType('string');
        $metadata->addField(new FieldMetadata('field5'))->setDataType('string');
        $context = $this->getContext();
        $context->getRequestType()->add('test');

        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap([
                ['val1', 'string', $context->getRequestType(), false, false, [], 'normalized1'],
                ['val5', 'string', $context->getRequestType(), false, false, [], 'normalized5']
            ]);

        $criteria = $this->upsertCriteriaBuilder->getUpsertFindEntityCriteria(
            $metadata,
            ['field1', 'field2', 'field3', 'field4', 'field5'],
            ['field1' => 'val1', 'field2' => 'val2', 'field4' => 'val4', 'field5' => 'val5'],
            '/meta/upsert',
            $context
        );
        self::assertEquals(
            [
                Error::createValidationError(
                    Constraint::VALUE,
                    'The "field2" field is not allowed because it is to-many association.'
                )->setSource(ErrorSource::createByPointer('/meta/upsert')),
                Error::createValidationError(
                    Constraint::VALUE,
                    'The "field3" field does not exist in the request data.'
                )->setSource(ErrorSource::createByPointer('/meta/upsert')),
                Error::createValidationError(
                    Constraint::VALUE,
                    'The "field4" field is unknown.'
                )->setSource(ErrorSource::createByPointer('/meta/upsert'))
            ],
            $context->getErrors()
        );
        self::assertNull($criteria);
    }
}
