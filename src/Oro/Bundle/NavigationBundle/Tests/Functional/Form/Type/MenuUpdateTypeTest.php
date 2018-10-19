<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Knp\Menu\ItemInterface;
use Oro\Bundle\FormBundle\Form\Type\OroIconType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Type\Stub\OroIconTypeStub;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;
use Oro\Bundle\NavigationBundle\Validator\Constraints\MaxNestedLevelValidator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;

class MenuUpdateTypeTest extends FormIntegrationTestCase
{
    const TEST_TITLE = 'Test Title';
    const TEST_DESCRIPTION = 'Test Description';
    const TEST_URI = 'http://test_uri';
    const TEST_ACL_RESOURCE_ID = 'test_acl_rescource_id';

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $kernel = $this->createMock(KernelInterface::class);

        return [
            new PreloadedExtension(
                [
                    new LocalizedFallbackValueCollectionType($registry),
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub(),
                    OroIconType::class => new OroIconTypeStub($kernel),
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testSubmitValid()
    {
        $menuUpdate = new MenuUpdate();
        $form = $this->factory->create(MenuUpdateType::class, $menuUpdate);

        $form->submit(
            [
                'titles' => [
                    'values' => [
                        'default' => self::TEST_TITLE
                    ]
                ],
                'descriptions' => [
                    'values' => [
                        'default' => self::TEST_DESCRIPTION
                    ]
                ],
                'icon'=> 'fa-anchor',
            ]
        );

        $expected = new MenuUpdate();
        $expectedTitle = (new LocalizedFallbackValue)->setString(self::TEST_TITLE);
        $expected->addTitle($expectedTitle);
        $expected->setIcon('fa-anchor');

        $expectedDescription = (new LocalizedFallbackValue)->setText(self::TEST_DESCRIPTION);
        $expected->addDescription($expectedDescription);

        $this->assertFormOptionEqual(true, 'disabled', $form->get('uri'));
        $this->assertFormNotContainsField('aclResourceId', $form);

        $this->assertFormIsValid($form);
        $this->assertEquals($expected, $form->getData());
    }

    public function testSubmitIsCustom()
    {
        $menuUpdate = new MenuUpdate();
        $menuUpdate->setCustom(true);

        $form = $this->factory->create(MenuUpdateType::class, $menuUpdate);

        $form->submit(
            [
                'titles' => [
                    'values' => [
                        'default' => self::TEST_TITLE
                    ]
                ],
                'uri' => self::TEST_URI
            ]
        );

        $expected = new MenuUpdate();
        $expectedTitle = (new LocalizedFallbackValue)->setString(self::TEST_TITLE);
        $expected
            ->setCustom(true)
            ->addTitle($expectedTitle)
            ->addDescription(new LocalizedFallbackValue)
            ->setUri(self::TEST_URI);

        $this->assertFormIsValid($form);
        $this->assertEquals($expected, $form->getData());
    }

    public function testSubmitEmptyTitle()
    {
        $menuUpdate = new MenuUpdate();
        $form = $this->factory->create(MenuUpdateType::class, $menuUpdate);

        $form->submit([]);

        $expected = new MenuUpdate();
        $expectedTitle = new LocalizedFallbackValue;
        $expected->addTitle($expectedTitle);
        $expected->addDescription(new LocalizedFallbackValue);

        $this->assertFormIsNotValid($form);
        $this->assertEquals($expected, $form->getData());
    }

    public function testSubmitCustomWithEmptyUri()
    {
        $menuUpdate = new MenuUpdate();
        $menuUpdate->setCustom(true);
        $form = $this->factory->create(MenuUpdateType::class, $menuUpdate);

        $form->submit(
            [
                'titles' => [
                    'values' => [
                        'default' => self::TEST_TITLE
                    ]
                ],
            ]
        );

        $expected = new MenuUpdate();
        $expectedTitle = (new LocalizedFallbackValue)->setString(self::TEST_TITLE);
        $expected
            ->setCustom(true)
            ->addDescription(new LocalizedFallbackValue)
            ->addTitle($expectedTitle);

        $this->assertFormIsNotValid($form);
        $this->assertEquals($expected, $form->getData());
    }

    public function testAclResourceIdShouldExist()
    {
        $menuUpdate = new MenuUpdate();
        $menuItem = $this->createMock(ItemInterface::class);
        $menuItem->expects($this->any())
            ->method('getExtra')
            ->with('acl_resource_id')
            ->willReturn(self::TEST_ACL_RESOURCE_ID);

        $form = $this->factory->create(MenuUpdateType::class, $menuUpdate, ['menu_item' => $menuItem]);

        $expected = new MenuUpdate();
        $expectedTitle = (new LocalizedFallbackValue)->setString(self::TEST_TITLE);
        $expected->addTitle($expectedTitle);
        $expected->addDescription(new LocalizedFallbackValue);

        $this->assertFormContainsField('aclResourceId', $form);
        $this->assertFormOptionEqual(true, 'disabled', $form->get('aclResourceId'));
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ConstraintValidatorFactoryInterface
     */
    protected function getConstraintValidatorFactory()
    {
        /* @var $factory \PHPUnit\Framework\MockObject\MockObject|ConstraintValidatorFactoryInterface */
        $factory = $this->createMock('Symfony\Component\Validator\ConstraintValidatorFactoryInterface');
        $factory->expects($this->any())
            ->method('getInstance')
            ->willReturnCallback(
                function (Constraint $constraint) {
                    $className = $constraint->validatedBy();

                    if ($className === MaxNestedLevelValidator::class) {
                        $this->validators[$className] = $this->getMockBuilder(MaxNestedLevelValidator::class)
                            ->disableOriginalConstructor()
                            ->getMock();
                    }

                    if (!isset($this->validators[$className]) ||
                        $className === 'Symfony\Component\Validator\Constraints\CollectionValidator'
                    ) {
                        $this->validators[$className] = new $className();
                    }

                    return $this->validators[$className];
                }
            );

        return $factory;
    }
}
