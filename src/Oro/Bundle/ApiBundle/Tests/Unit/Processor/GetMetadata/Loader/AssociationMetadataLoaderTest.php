<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\AssociationMetadataLoader;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestMetadataExtra;

class AssociationMetadataLoaderTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_CLASS_NAME        = 'Test\Class';
    private const TEST_VERSION           = '1.1';
    private const TEST_REQUEST_TYPE      = RequestType::REST;
    private const TEST_TARGET_CLASS_NAME = 'Test\TargetClass';

    /** @var MetadataContext */
    private $context;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataProvider */
    private $metadataProvider;

    /** @var AssociationMetadataLoader */
    private $associationMetadataLoader;

    protected function setUp()
    {
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->associationMetadataLoader = new AssociationMetadataLoader($this->metadataProvider);

        $this->context = new MetadataContext();
        $this->context->setClassName(self::TEST_CLASS_NAME);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $this->context->setExtras([new TestMetadataExtra('test')]);
        $this->context->setWithExcludedProperties(true);
    }

    public function testWhenAssociationTargetMetadataAlreadyExists()
    {
        $config = new EntityDefinitionConfig();
        $fieldConfig = $config->addField('association');
        $fieldConfig->setTargetClass(self::TEST_TARGET_CLASS_NAME);
        $fieldConfig->createAndSetTargetEntity();

        $entityMetadata = new EntityMetadata();
        $association = $entityMetadata->addAssociation(new AssociationMetadata('association'));
        $association->setTargetClassName(self::TEST_TARGET_CLASS_NAME);
        $association->setTargetMetadata(new EntityMetadata());

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->associationMetadataLoader->completeAssociationMetadata(
            $entityMetadata,
            $config,
            $this->context
        );
    }

    public function testAssociationWithoutFieldConfig()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata();
        $association = $entityMetadata->addAssociation(new AssociationMetadata('association'));
        $association->setTargetClassName(self::TEST_TARGET_CLASS_NAME);

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->associationMetadataLoader->completeAssociationMetadata(
            $entityMetadata,
            $config,
            $this->context
        );
    }

    public function testAssociationWithoutFieldTargetConfig()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('association');

        $entityMetadata = new EntityMetadata();
        $association = $entityMetadata->addAssociation(new AssociationMetadata('association'));
        $association->setTargetClassName(self::TEST_TARGET_CLASS_NAME);

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->associationMetadataLoader->completeAssociationMetadata(
            $entityMetadata,
            $config,
            $this->context
        );
    }

    public function testAssociationWhenTargetMetadataNotFound()
    {
        $config = new EntityDefinitionConfig();
        $fieldConfig = $config->addField('association');
        $fieldConfig->setTargetClass(self::TEST_TARGET_CLASS_NAME);
        $targetConfig = $fieldConfig->createAndSetTargetEntity();

        $entityMetadata = new EntityMetadata();
        $association = $entityMetadata->addAssociation(new AssociationMetadata('association'));
        $association->setTargetClassName(self::TEST_TARGET_CLASS_NAME);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                self::TEST_TARGET_CLASS_NAME,
                self::TEST_VERSION,
                new RequestType([self::TEST_REQUEST_TYPE]),
                self::identicalTo($targetConfig),
                [new TestMetadataExtra('test')],
                true
            )
            ->willReturn(null);

        $this->associationMetadataLoader->completeAssociationMetadata(
            $entityMetadata,
            $config,
            $this->context
        );

        self::assertNull($association->getTargetMetadata());
    }

    public function testAssociationWhenTargetMetadataFound()
    {
        $config = new EntityDefinitionConfig();
        $fieldConfig = $config->addField('association');
        $fieldConfig->setTargetClass(self::TEST_TARGET_CLASS_NAME);
        $targetConfig = $fieldConfig->createAndSetTargetEntity();

        $entityMetadata = new EntityMetadata();
        $association = $entityMetadata->addAssociation(new AssociationMetadata('association'));
        $association->setTargetClassName(self::TEST_TARGET_CLASS_NAME);

        $targetMetadata = new EntityMetadata();

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                self::TEST_TARGET_CLASS_NAME,
                self::TEST_VERSION,
                new RequestType([self::TEST_REQUEST_TYPE]),
                self::identicalTo($targetConfig),
                [new TestMetadataExtra('test')],
                true
            )
            ->willReturn($targetMetadata);

        $this->associationMetadataLoader->completeAssociationMetadata(
            $entityMetadata,
            $config,
            $this->context
        );

        self::assertSame($targetMetadata, $association->getTargetMetadata());
    }

    public function testAssociationWhenItsTargetClassIsNotEqualToTargetClassInConfig()
    {
        $config = new EntityDefinitionConfig();
        $fieldConfig = $config->addField('association');
        $fieldConfig->setTargetClass('Test\TargetClassFromConfig');
        $targetConfig = $fieldConfig->createAndSetTargetEntity();

        $entityMetadata = new EntityMetadata();
        $association = $entityMetadata->addAssociation(new AssociationMetadata('association'));
        $association->setTargetClassName(self::TEST_TARGET_CLASS_NAME);
        $association->addAcceptableTargetClassName(self::TEST_TARGET_CLASS_NAME);

        $targetMetadata = new EntityMetadata();

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                'Test\TargetClassFromConfig',
                self::TEST_VERSION,
                new RequestType([self::TEST_REQUEST_TYPE]),
                self::identicalTo($targetConfig),
                [new TestMetadataExtra('test')],
                true
            )
            ->willReturn($targetMetadata);

        $this->associationMetadataLoader->completeAssociationMetadata(
            $entityMetadata,
            $config,
            $this->context
        );

        self::assertSame($targetMetadata, $association->getTargetMetadata());
        self::assertEquals('Test\TargetClassFromConfig', $association->getTargetClassName());
        self::assertEquals(['Test\TargetClassFromConfig'], $association->getAcceptableTargetClassNames());
    }
}
