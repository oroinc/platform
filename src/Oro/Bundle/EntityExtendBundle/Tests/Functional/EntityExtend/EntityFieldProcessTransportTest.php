<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\EntityExtend;

use Doctrine\ORM\Proxy\Proxy;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class EntityFieldProcessTransportTest extends WebTestCase
{
    #[\Override]
    public function setUp(): void
    {
        $this->bootKernel();
    }

    public function testGetSetObject(): void
    {
        $entityFieldProcess = new EntityFieldProcessTransport();
        self::assertNull($entityFieldProcess->getObject());
        $user = new User();
        $setObject = $entityFieldProcess->setObject($user);

        self::assertSame($setObject::class, EntityFieldProcessTransport::class);
        self::assertSame($entityFieldProcess->getObject(), $user);
    }

    public function testGetSetClass(): void
    {
        $entityFieldProcess = new EntityFieldProcessTransport();

        self::assertSame('', $entityFieldProcess->getClass());
        $entityFieldProcess->setClass(User::class);

        self::assertSame($entityFieldProcess->getClass(), User::class);

        $proxyClassAlias = '\\' . Proxy::MARKER . '\\' . AttributeFamily::class;
        class_alias(AttributeFamily::class, $proxyClassAlias);
        $entityMetadata = new EntityFieldProcessTransport();
        $entityMetadata->setClass($proxyClassAlias);

        self::assertSame(AttributeFamily::class, $entityMetadata->getClass());
    }

    public function testGetSetStorage(): void
    {
        $entityFieldProcess = new EntityFieldProcessTransport();
        self::assertNull($entityFieldProcess->getStorage());
        $testStorage = new \ArrayObject();
        $setStorage = $entityFieldProcess->setStorage($testStorage);

        self::assertSame($setStorage::class, EntityFieldProcessTransport::class);
        self::assertSame($setStorage->getStorage(), $testStorage);
    }

    public function testGetSetObjectVar(): void
    {
        $entityFieldProcess = new EntityFieldProcessTransport();
        self::assertNull($entityFieldProcess->getObjectVar('undefinedObjectVar'));

        $setObjectVars = $entityFieldProcess->setObjectVars(['test' => 'objVar1']);
        self::assertSame($setObjectVars::class, EntityFieldProcessTransport::class);
        self::assertSame($entityFieldProcess->getObjectVar('test'), 'objVar1');
    }

    public function testGetSetName(): void
    {
        $entityFieldProcess = new EntityFieldProcessTransport();
        self::assertSame('', $entityFieldProcess->getName());
        $setName = $entityFieldProcess->setName('TestName');

        self::assertSame($setName::class, EntityFieldProcessTransport::class);
        self::assertSame('TestName', $entityFieldProcess->getName());
    }

    public function testGetSetArguments(): void
    {
        $entityFieldProcess = new EntityFieldProcessTransport();
        self::assertSame([], $entityFieldProcess->getArguments());
        self::assertSame(null, $entityFieldProcess->getArgument(0));
        $setArguments = $entityFieldProcess->setArguments(['arg1', 'arg2']);

        self::assertSame($setArguments::class, EntityFieldProcessTransport::class);
        self::assertSame('arg1', $entityFieldProcess->getArgument(0));
        self::assertSame('arg2', $entityFieldProcess->getArgument(1));
        self::assertSame(['arg1', 'arg2'], $entityFieldProcess->getArguments());
    }

    public function testGetSetValue(): void
    {
        $entityFieldProcess = new EntityFieldProcessTransport();
        self::assertSame(null, $entityFieldProcess->getValue());
        $setValue = $entityFieldProcess->setValue('testValue');

        self::assertSame($setValue::class, EntityFieldProcessTransport::class);
        self::assertSame('testValue', $entityFieldProcess->getValue());
    }

    public function testGetSetResult(): void
    {
        $entityFieldProcess = new EntityFieldProcessTransport();
        self::assertSame(null, $entityFieldProcess->getResult());
        $setResult = $entityFieldProcess->setResult('testResult');

        self::assertSame($setResult::class, EntityFieldProcessTransport::class);
        self::assertSame('testResult', $entityFieldProcess->getResult());
    }

    public function testIsProcessed(): void
    {
        $entityFieldProcess = new EntityFieldProcessTransport();
        self::assertSame(false, $entityFieldProcess->isProcessed());
        $setResult = $entityFieldProcess->setProcessed(true);

        self::assertSame($setResult::class, EntityFieldProcessTransport::class);
        self::assertSame(true, $entityFieldProcess->isProcessed());

        $entityFieldProcess->setProcessed(false);
        self::assertSame(false, $entityFieldProcess->isProcessed());
    }
}
