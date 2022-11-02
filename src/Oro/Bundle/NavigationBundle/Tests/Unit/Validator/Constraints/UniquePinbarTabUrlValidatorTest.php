<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NavigationBundle\Entity\NavigationItem;
use Oro\Bundle\NavigationBundle\Entity\Repository\PinbarTabRepository;
use Oro\Bundle\NavigationBundle\Exception\LogicException;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\PinbarTabStub;
use Oro\Bundle\NavigationBundle\Utils\PinbarTabUrlNormalizerInterface;
use Oro\Bundle\NavigationBundle\Validator\Constraints\UniquePinbarTabUrl;
use Oro\Bundle\NavigationBundle\Validator\Constraints\UniquePinbarTabUrlValidator;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniquePinbarTabUrlValidatorTest extends ConstraintValidatorTestCase
{
    private const URL = 'sample-url';
    private const URL_NORMALIZED = 'sample-url-normalized';
    private const TYPE = 'sample-type';
    private const PINBAR_TAB_CLASS_NAME = PinbarTabStub::class;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var PinbarTabUrlNormalizerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $pinbarTabUrlNormalizer;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->pinbarTabUrlNormalizer = $this->createMock(PinbarTabUrlNormalizerInterface::class);
        parent::setUp();
    }

    private function createNavigationItem(): NavigationItem
    {
        return new NavigationItem([
            'url' => self::URL,
            'type' => self::TYPE,
            'user' => $this->createMock(AbstractUser::class),
            'organization' => $this->createMock(OrganizationInterface::class),
        ]);
    }

    protected function createValidator()
    {
        return new UniquePinbarTabUrlValidator($this->doctrineHelper, $this->pinbarTabUrlNormalizer);
    }

    public function testValidateWhenNotAbstractPinbarTab(): void
    {
        $constraint = new UniquePinbarTabUrl(['pinbarTabClass' => self::PINBAR_TAB_CLASS_NAME]);
        $this->validator->validate(new \stdClass(), $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNoNavigationItem(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('PinbarTab does not contain NavigationItem');

        $constraint = new UniquePinbarTabUrl(['pinbarTabClass' => self::PINBAR_TAB_CLASS_NAME]);
        $this->validator->validate(new PinbarTabStub(), $constraint);
    }

    public function testValidate(): void
    {
        $entity = new PinbarTabStub();
        $entity->setItem($navigationItem = $this->createNavigationItem());

        $this->pinbarTabUrlNormalizer->expects(self::once())
            ->method('getNormalizedUrl')
            ->with(self::URL)
            ->willReturn(self::URL_NORMALIZED);

        $pinbarTabRepository = $this->createMock(PinbarTabRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(self::PINBAR_TAB_CLASS_NAME)
            ->willReturn($pinbarTabRepository);
        $pinbarTabRepository->expects(self::once())
            ->method('countNavigationItems')
            ->with(
                self::URL_NORMALIZED,
                $navigationItem->getUser(),
                $navigationItem->getOrganization(),
                $navigationItem->getType()
            )
            ->willReturn(0);

        $constraint = new UniquePinbarTabUrl(['pinbarTabClass' => self::PINBAR_TAB_CLASS_NAME]);
        $this->validator->validate($entity, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenAlreadyExists(): void
    {
        $entity = new PinbarTabStub();
        $entity->setItem($navigationItem = $this->createNavigationItem());

        $this->pinbarTabUrlNormalizer->expects(self::once())
            ->method('getNormalizedUrl')
            ->with(self::URL)
            ->willReturn(self::URL_NORMALIZED);

        $pinbarTabRepository = $this->createMock(PinbarTabRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(self::PINBAR_TAB_CLASS_NAME)
            ->willReturn($pinbarTabRepository);
        $pinbarTabRepository->expects(self::once())
            ->method('countNavigationItems')
            ->with(
                self::URL_NORMALIZED,
                $navigationItem->getUser(),
                $navigationItem->getOrganization(),
                $navigationItem->getType()
            )
            ->willReturn(1);

        $constraint = new UniquePinbarTabUrl(['pinbarTabClass' => self::PINBAR_TAB_CLASS_NAME]);
        $this->validator->validate($entity, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}
