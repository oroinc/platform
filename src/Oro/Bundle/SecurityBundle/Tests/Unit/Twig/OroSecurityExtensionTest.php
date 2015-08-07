<?php

namespace Oro\Bundle\SecurityBundle\Tests\Twig;

use Oro\Bundle\SecurityBundle\Twig\OroSecurityExtension;

class OroSecurityExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroSecurityExtension
     */
    protected $twigExtension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $aclCache;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $nameFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclCache = $this->getMockBuilder('Symfony\Component\Security\Acl\Model\AclCacheInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->nameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->twigExtension = new OroSecurityExtension(
            $this->manager,
            $this->aclCache,
            $this->securityFacade,
            $this->nameFormatter,
            $this->translator
        );
    }

    protected function tearDown()
    {
        unset($this->securityFacade);
        unset($this->twigExtension);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_security_extension', $this->twigExtension->getName());
    }

    public function testGetFunctions()
    {
        $expectedFunctions = array(
            'resource_granted' => 'checkResourceIsGranted',
            'format_share_scopes' => 'formatShareScopes',
            'oro_share_count' => 'getShareCount',
            'oro_shared_with_name' => 'getSharedWithName',
        );

        $actualFunctions = $this->twigExtension->getFunctions();
        $this->assertSameSize($expectedFunctions, $actualFunctions);

        foreach ($expectedFunctions as $twigFunction => $internalMethod) {
            $this->assertArrayHasKey($twigFunction, $actualFunctions);
            $this->assertInstanceOf('\Twig_Function_Method', $actualFunctions[$twigFunction]);
            $this->assertAttributeEquals($internalMethod, 'method', $actualFunctions[$twigFunction]);
        }
    }

    public function testCheckResourceIsGranted()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('test_acl'))
            ->will($this->returnValue(true));

        $this->assertTrue($this->twigExtension->checkResourceIsGranted('test_acl'));
    }

    public function testFormatShareScopesWithEmptyValue()
    {
        $this->translator->expects($this->at(0))
            ->method('trans')
            ->with('oro.security.share_scopes.not_available')
            ->willReturn('N/A');
        $this->assertEquals('N/A', $this->twigExtension->formatShareScopes(null));
    }

    /**
     * @expectedException \LogicException
     */
    public function testFormatShareScopesWithLoginException()
    {
        $this->twigExtension->formatShareScopes(new \stdClass());
    }

    public function testFormatShareScopesWithString()
    {
        $this->translator->expects($this->at(0))
            ->method('trans')
            ->with('oro.security.share_scopes.business_unit.short_label')
            ->willReturn('business_unit_short_label_translated');

        $this->translator->expects($this->at(1))
            ->method('trans')
            ->with('oro.security.share_scopes.user.short_label')
            ->willReturn('user_short_label_translated');

        $this->assertEquals(
            'business_unit_short_label_translated, user_short_label_translated',
            $this->twigExtension->formatShareScopes(json_encode(['business_unit', 'user']), 'short_label')
        );
    }

    public function testFormatShareScopesWithArray()
    {
        $this->translator->expects($this->at(0))
            ->method('trans')
            ->with('oro.security.share_scopes.business_unit.label')
            ->willReturn('business_unit_label_translated');

        $this->translator->expects($this->at(1))
            ->method('trans')
            ->with('oro.security.share_scopes.user.label')
            ->willReturn('user_label_translated');

        $this->assertEquals(
            'business_unit_label_translated, user_label_translated',
            $this->twigExtension->formatShareScopes(['business_unit', 'user'])
        );
    }
}
