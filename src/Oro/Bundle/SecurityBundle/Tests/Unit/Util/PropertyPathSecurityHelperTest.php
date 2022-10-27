<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Util;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsArticle;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser;
use Oro\Bundle\SecurityBundle\Util\PropertyPathSecurityHelper;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PropertyPathSecurityHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigProvider;

    /** @var PropertyPathSecurityHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);

        $this->helper = new PropertyPathSecurityHelper(
            $this->authorizationChecker,
            $this->managerRegistry,
            $this->entityConfigProvider
        );
    }

    public function testIsGrantedByPropertyPath()
    {
        $address = new CmsAddress();
        $address->city = 'test';
        $user = new CmsUser();
        $user->id = 1;
        $user->setAddress($address);
        $article = new CmsArticle();
        $article->setAuthor($user);

        $addressMetadata = new ClassMetadata(get_class($address));
        $userMetadata = new ClassMetadata(get_class($user));
        $user->associationMappings = [
            'address' => ['targetEntity' => get_class($address)]
        ];
        $articleMetadata = new ClassMetadata(get_class($article));
        $articleMetadata->associationMappings = [
            'user' => ['targetEntity' => get_class($user)]
        ];

        $propertyPath = 'user.address.city';

        $em = $this->createMock(ObjectManager::class);
        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturnMap([
                [get_class($address), $addressMetadata],
                [get_class($user), $userMetadata],
                [get_class($article), $articleMetadata]
            ]);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(function ($permission, $object) {
                return !(($object instanceof FieldVote) && $object->getField() === 'city');
            });

        $isGranted = $this->helper->isGrantedByPropertyPath($article, $propertyPath, 'EDIT');
        $this->assertFalse($isGranted);
    }

    public function testIisGrantedByPropertyPathOnWrongClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Can't get entity manager for class stdClass");

        $this->helper->isGrantedByPropertyPath(new \stdClass(), 'somePath', 'EDIT');
    }
}
