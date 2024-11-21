<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeSubresource;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource\PrepareFormData;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeSubresourceProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\PropertyAccess\PropertyAccess;

class PrepareFormDataTest extends ChangeSubresourceProcessorTestCase
{
    private PrepareFormData $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new PrepareFormData(PropertyAccess::createPropertyAccessor());
    }

    public function testProcessWhenFormDataAlreadyPrepared(): void
    {
        $associationName = 'association';
        $requestData = [$associationName => ['key' => 'value']];
        $formData = [$associationName => new \stdClass()];

        $this->context->setAssociationName($associationName);
        $this->context->setRequestData($requestData);
        $this->context->setResult($formData);
        $this->processor->process($this->context);

        self::assertEquals($requestData, $this->context->getRequestData());
        self::assertEquals($formData, $this->context->getResult());
    }

    public function testProcessWhenAssociationDataExist(): void
    {
        $parentEntity = new User();
        $associationName = 'businessUnits';
        $requestData = ['key' => 'value'];

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName);

        $this->context->setParentEntity($parentEntity);
        $this->context->setAssociationName($associationName);
        $this->context->setRequestData($requestData);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);

        self::assertEquals([$associationName => $requestData], $this->context->getRequestData());
        self::assertEquals(
            [$associationName => $parentEntity->getBusinessUnits()],
            $this->context->getResult()
        );
    }

    public function testProcessWhenRenamedAssociationDataExist(): void
    {
        $parentEntity = new User();
        $associationName = 'renamedBusinessUnits';
        $requestData = ['key' => 'value'];

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setPropertyPath('businessUnits');

        $this->context->setParentEntity($parentEntity);
        $this->context->setAssociationName($associationName);
        $this->context->setRequestData($requestData);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);

        self::assertEquals([$associationName => $requestData], $this->context->getRequestData());
        self::assertEquals(
            [$associationName => $parentEntity->getBusinessUnits()],
            $this->context->getResult()
        );
    }

    public function testProcessWhenAssociationDataExistButNoFieldConfig(): void
    {
        $parentEntity = new User();
        $associationName = 'businessUnits';
        $requestData = ['key' => 'value'];

        $this->context->setParentEntity($parentEntity);
        $this->context->setAssociationName($associationName);
        $this->context->setRequestData($requestData);
        $this->context->setParentConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        self::assertEquals([$associationName => $requestData], $this->context->getRequestData());
        self::assertEquals(
            [$associationName => $parentEntity->getBusinessUnits()],
            $this->context->getResult()
        );
    }

    public function testProcessWhenAssociationDataExistButNoParentEntityConfig(): void
    {
        $parentEntity = new User();
        $associationName = 'businessUnits';
        $requestData = ['key' => 'value'];

        $this->context->setParentEntity($parentEntity);
        $this->context->setAssociationName($associationName);
        $this->context->setRequestData($requestData);
        $this->context->setParentConfig(null);
        $this->processor->process($this->context);

        self::assertEquals([$associationName => $requestData], $this->context->getRequestData());
        self::assertEquals(
            [$associationName => $parentEntity->getBusinessUnits()],
            $this->context->getResult()
        );
    }

    public function testProcessWhenAssociationDataDoesNotExist(): void
    {
        $parentEntity = new User();
        $associationName = 'notExisting';
        $requestData = ['key' => 'value'];

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->set(ConfigUtil::REQUEST_TARGET_CLASS, \stdClass::class);
        $parentConfig->addField($associationName);

        $this->context->setParentEntity($parentEntity);
        $this->context->setAssociationName($associationName);
        $this->context->setRequestData($requestData);
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);

        self::assertEquals([$associationName => $requestData], $this->context->getRequestData());
        self::assertEquals(
            [$associationName => new \stdClass()],
            $this->context->getResult()
        );
    }
}
