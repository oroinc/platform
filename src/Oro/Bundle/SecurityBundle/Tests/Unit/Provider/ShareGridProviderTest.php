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
    protected $entityClassNameHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $shareScopeProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

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
        $this->entityClassNameHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturn($this->configProvider);
        $this->shareScopeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Provider\ShareScopeProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new ShareGridProvider(
            $this->securityFacade,
            $this->entityClassNameHelper,
            $this->configManager,
            $this->shareScopeProvider,
            $this->translator
        );
    }

    public function testGetSupportedGridsInfoWhenNoClassConfig()
    {
        $this->entityClassNameHelper->expects($this->once())
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
        $this->entityClassNameHelper->expects($this->once())
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
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->assertEquals([], $this->provider->getSupportedGridsInfo(self::ENTITY_CLASS));
    }

    public function testGetSupportedGridsInfo()
    {
        $this->entityClassNameHelper->expects($this->exactly(2))
            ->method('resolveEntityClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn(self::ENTITY_CLASS);
        $this->configManager->expects($this->any())
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
        $this->configProvider->expects($this->at(0))
            ->method('getConfig')
            ->willReturn($shareScopesConfig);
        $labelConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $labelConfig->expects($this->once())
            ->method('get')
            ->with('label')
            ->willReturn(self::ENTITY_LABEL);
        $this->configProvider->expects($this->at(1))
            ->method('getConfig')
            ->willReturn($labelConfig);
        $shareGridConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $shareGridConfig->expects($this->once())
            ->method('get')
            ->with('share_grid')
            ->willReturn(self::GRID_NAME);
        $this->configProvider->expects($this->at(2))
            ->method('getConfig')
            ->willReturn($shareGridConfig);
        $this->shareScopeProvider->expects($this->once())
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
