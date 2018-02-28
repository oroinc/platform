<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Twig;

use Oro\Bundle\UserBundle\Model\Gender;
use Oro\Bundle\UserBundle\Provider\GenderProvider;
use Oro\Bundle\UserBundle\Twig\OroUserExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class OroUserExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var OroUserExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $genderProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $tokenStorage;

    protected function setUp()
    {
        $this->genderProvider = $this->getMockBuilder(GenderProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_user.gender_provider', $this->genderProvider)
            ->add('security.token_storage', $this->tokenStorage)
            ->getContainer($this);

        $this->extension = new OroUserExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals('user_extension', $this->extension->getName());
    }

    public function testGetGenderLabel()
    {
        $label = 'Male';
        $this->genderProvider->expects($this->once())
            ->method('getLabelByName')
            ->with(Gender::MALE)
            ->will($this->returnValue($label));

        $this->assertNull(
            self::callTwigFunction($this->extension, 'oro_gender', [null])
        );
        $this->assertEquals(
            $label,
            self::callTwigFunction($this->extension, 'oro_gender', [Gender::MALE])
        );
    }
}
