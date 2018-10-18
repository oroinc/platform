<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authorization;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RequestAuthorizationCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $annotationProvider;

    /** @var RequestAuthorizationChecker */
    private $requestAuthorizationChecker;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->annotationProvider = $this->createMock(AclAnnotationProvider::class);

        $container = TestContainerBuilder::create()
            ->add('entity_class_resolver', $this->entityClassResolver)
            ->add('annotation_provider', $this->annotationProvider)
            ->getContainer($this);

        $this->requestAuthorizationChecker = new RequestAuthorizationChecker(
            $this->authorizationChecker,
            new ServiceLink($container, 'entity_class_resolver'),
            new ServiceLink($container, 'annotation_provider')
        );
    }

    public function testGetRequestAcl()
    {
        $request = new Request();
        $request->attributes->add(['_controller' => 'OroTestBundle::testAction']);
        $acl = new Acl(['id' => 1, 'class' => 'OroTestBundle:Test', 'type' => 'entity']);
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotation')
            ->with('OroTestBundle', 'testAction')
            ->will($this->returnValue($acl));
        $this->entityClassResolver->expects($this->once())
            ->method('isEntity')
            ->with('OroTestBundle:Test')
            ->will($this->returnValue(true));
        $this->entityClassResolver->expects($this->once())
            ->method('getEntityClass')
            ->with('OroTestBundle:Test')
            ->will($this->returnValue('Oro\Bundle\TestBundle\Entity\Test'));

        $returnAcl = $this->requestAuthorizationChecker->getRequestAcl($request, true);
        $this->assertNotNull($returnAcl);
        $this->assertEquals('Oro\Bundle\TestBundle\Entity\Test', $acl->getClass());
    }

    public function testGeWrongRequestAcl()
    {
        $request = new Request();
        $request->attributes->add(['_controller' => 'wrong controller']);
        $this->annotationProvider->expects($this->never())
            ->method('findAnnotation');
        $this->entityClassResolver->expects($this->never())
            ->method('isEntity');
        $this->entityClassResolver->expects($this->never())
            ->method('getEntityClass');

        $this->assertNull($this->requestAuthorizationChecker->getRequestAcl($request, true));
    }

    /**
     * @dataProvider isRequestObjectIsGrantedProvider
     */
    public function testIsRequestObjectIsGranted($requestController, $isGrant, $result)
    {
        $object = new \stdClass();
        $request = new Request();
        $request->attributes->add(['_controller' => $requestController]);
        $acl = new Acl(
            ['id' => 1, 'class' => 'OroTestBundle:Test', 'type' => 'entity', 'permission' => 'TEST_PERMISSION']
        );
        $this->annotationProvider->expects($this->any())
            ->method('findAnnotation')
            ->will($this->returnValue($acl));
        $this->entityClassResolver->expects($this->any())
            ->method('isEntity')
            ->with('OroTestBundle:Test')
            ->will($this->returnValue(true));
        $this->entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->with('OroTestBundle:Test')
            ->will($this->returnValue('\stdClass'));
        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($object))
            ->will($this->returnValue($isGrant));

        $this->assertEquals(
            $result,
            $this->requestAuthorizationChecker->isRequestObjectIsGranted($request, $object)
        );
    }

    public function isRequestObjectIsGrantedProvider()
    {
        return [
            ['testBundle::testAction', true, 1],
            ['testBundle::testAction', false, -1],
            ['wrong_action', true, 0]
        ];
    }
}
