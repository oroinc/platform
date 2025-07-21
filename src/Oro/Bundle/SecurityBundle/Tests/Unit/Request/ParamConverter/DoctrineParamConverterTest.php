<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Request\ParamConverter;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker;
use Oro\Bundle\SecurityBundle\Request\ParamConverter\DoctrineParamConverter;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DoctrineParamConverterTest extends TestCase
{
    private ManagerRegistry&MockObject $registry;
    private RequestAuthorizationChecker&MockObject $requestAuthorizationChecker;
    private DoctrineParamConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->requestAuthorizationChecker = $this->createMock(RequestAuthorizationChecker::class);

        $this->converter = new DoctrineParamConverter(
            $this->registry,
            $this->createMock(ExpressionLanguage::class),
            $this->requestAuthorizationChecker
        );
    }

    /**
     * @dataProvider idsProvider
     */
    public function testApply(CmsAddress $object, int $isGranted, string $class, bool $isCorrectClass): void
    {
        $manager = $this->createMock(ObjectManager::class);
        $objectRepository = $this->createMock(ObjectRepository::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->willReturn($objectRepository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($manager);
        $objectRepository->expects($this->any())
            ->method('find')
            ->willReturn($object);

        $request = new Request();
        $request->attributes->set('_oro_access_checked', false);
        $request->attributes->set('id', 1);
        $config = new ParamConverter(
            [
                'class' => get_class($object),
                'name' => 'arg',
                'options' => ['id' => 'id']
            ]
        );
        $attribute = Acl::fromArray(
            [
                'id'         => 1,
                'type'       => 'entity',
                'class'      => $class,
                'permission' => 'EDIT'
            ]
        );
        $this->requestAuthorizationChecker->expects($this->any())
            ->method('isRequestObjectIsGranted')
            ->willReturn($isGranted);

        if ($isGranted === -1) {
            $this->expectException(AccessDeniedException::class);
            $this->expectExceptionMessage('You do not get EDIT permission for this object');
            $this->requestAuthorizationChecker->expects($this->any())
                ->method('getRequestAcl')
                ->willReturn($attribute);
        }

        $this->converter->apply($request, $config);

        $this->assertTrue($request->attributes->has('_oro_access_checked'));

        if ($isGranted === -1 || !$isCorrectClass) {
            $this->assertFalse($request->attributes->get('_oro_access_checked'));
        }
        if ($isGranted === 0) {
            $this->assertTrue($request->attributes->get('_oro_access_checked'));
        }
    }

    public function idsProvider(): array
    {
        return [
            [new CmsAddress(), 1, CmsAddress::class, true],
            [new CmsAddress(), -1, CmsAddress::class, true],
            [new CmsAddress(), -1, 'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\wrongClass', false],
            [new CmsAddress(), -1, 'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\wrongClass', false],
        ];
    }

    public function testSupportsWithoutClass(): void
    {
        $config = new ParamConverter([]);

        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $this->registry->expects($this->never())
            ->method('getManager');

        $this->assertFalse($this->converter->supports($config));
    }

    public function testSupportsWithoutConfiguredEntityManager(): void
    {
        $config = new ParamConverter(['class' => 'stdClass']);

        $objectManager = $this->createMock(ObjectManager::class);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('stdClass')
            ->willReturn($objectManager);

        $this->assertTrue($this->converter->supports($config));
    }

    public function testSupportsWithoutConfiguredEntityManagerAndNotManageableClass(): void
    {
        $config = new ParamConverter(['class' => 'stdClass']);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('stdClass')
            ->willReturn(null);

        $this->assertFalse($this->converter->supports($config));
    }

    public function testSupportsWithConfiguredEntityManager(): void
    {
        $config = new ParamConverter(['class' => 'stdClass']);
        $config->setOptions(['entity_manager' => 'foo']);

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->once())
            ->method('isTransient')
            ->with('stdClass')
            ->willReturn(false);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $this->registry->expects($this->once())
            ->method('getManager')
            ->with('foo')
            ->willReturn($objectManager);

        $this->assertTrue($this->converter->supports($config));
    }

    public function testSupportsWithConfiguredEntityManagerAndTransientClass(): void
    {
        $config = new ParamConverter(['class' => 'stdClass']);
        $config->setOptions(['entity_manager' => 'foo']);

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->once())
            ->method('isTransient')
            ->with('stdClass')
            ->willReturn(true);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $this->registry->expects($this->once())
            ->method('getManager')
            ->with('foo')
            ->willReturn($objectManager);

        $this->assertFalse($this->converter->supports($config));
    }
}
