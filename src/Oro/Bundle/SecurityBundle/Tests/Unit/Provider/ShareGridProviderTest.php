<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Provider;

use Oro\Bundle\SecurityBundle\Provider\ShareGridProvider;

class ShareGridProviderTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Oro\Bundle\UserBundle\Entity\User';
    const ENTITY_LABEL = 'User';
    const SHARE_SCOPE = 'user';
    const GRID_NAME = 'share-with-users-datagrid';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $routingHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $helper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var ShareGridProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->with('VIEW')
            ->willReturn(true);
        $this->routingHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->helper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Search\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new ShareGridProvider(
            $this->securityFacade,
            $this->routingHelper,
            $this->configManager,
            $this->helper,
            $this->translator
        );
    }

    public function testGetSupportedGridsInfoWhenNoClassConfig()
    {
        $this->routingHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn(self::ENTITY_CLASS);
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(null);

        $this->assertEquals([], $this->provider->getSupportedGridsInfo(self::ENTITY_CLASS));
    }

    public function testGetSupportedGridsInfoWhenNoSharingScopes()
    {
        $this->routingHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn(self::ENTITY_CLASS);
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('share_scopes')
            ->willReturn(null);
        $this->configManager->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->assertEquals([], $this->provider->getSupportedGridsInfo(self::ENTITY_CLASS));
    }

    public function testGetSupportedGridsInfo()
    {
        $this->routingHelper->expects($this->exactly(2))
            ->method('resolveEntityClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn(self::ENTITY_CLASS);
        $this->configManager->expects($this->at(0))
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $shareScopesConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $shareScopesConfig->expects($this->once())
            ->method('get')
            ->with('share_scopes')
            ->willReturn([self::SHARE_SCOPE]);
        $this->configManager->expects($this->at(1))
            ->method('getConfig')
            ->willReturn($shareScopesConfig);
        $this->configManager->expects($this->at(2))
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $labelConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $labelConfig->expects($this->once())
            ->method('get')
            ->with('label')
            ->willReturn(self::ENTITY_LABEL);
        $this->configManager->expects($this->at(3))
            ->method('getConfig')
            ->willReturn($labelConfig);
        $this->configManager->expects($this->at(4))
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $shareGridConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $shareGridConfig->expects($this->once())
            ->method('get')
            ->with('share_with_datagrid')
            ->willReturn(self::GRID_NAME);
        $this->configManager->expects($this->at(5))
            ->method('getConfig')
            ->willReturn($shareGridConfig);
        $this->helper->expects($this->once())
            ->method('getClassNamesBySharingScopes')
            ->with([self::SHARE_SCOPE])
            ->willReturn([self::ENTITY_CLASS]);
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(self::ENTITY_LABEL)
            ->willReturn(self::ENTITY_LABEL);

        $this->assertEquals(
            [
                0 => [
                    'isGranted' => true,
                    'label' => self::ENTITY_LABEL,
                    'className' => self::ENTITY_CLASS,
                    'first' => true,
                    'gridName' => self::GRID_NAME,
                ],
            ],
            $this->provider->getSupportedGridsInfo(self::ENTITY_CLASS)
        );
    }
}
