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
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UniquePinbarTabUrlValidatorTest extends \PHPUnit\Framework\TestCase
{
    private const URL = 'sample-url';
    private const URL_NORMALIZED = 'sample-url-normalized';
    private const TYPE = 'sample-type';
    private const PINBAR_TAB_CLASS_NAME = PinbarTabStub::class;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var PinbarTabUrlNormalizerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $pinbarTabUrlNormalizer;

    /** @var Constraint|\PHPUnit\Framework\MockObject\MockObject */
    private $constraint;

    /** @var UniquePinbarTabUrlValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->pinbarTabUrlNormalizer = $this->createMock(PinbarTabUrlNormalizerInterface::class);

        $this->constraint = new UniquePinbarTabUrl(['pinbarTabClass' => self::PINBAR_TAB_CLASS_NAME]);

        $this->validator = new UniquePinbarTabUrlValidator($this->doctrineHelper, $this->pinbarTabUrlNormalizer);
    }

    public function testValidateWhenNotAbstractPinbarTab(): void
    {
        $this->validator->initialize($context = $this->createMock(ExecutionContextInterface::class));

        $context
            ->expects(self::never())
            ->method('buildViolation');

        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    public function testValidateWhenNoNavigationItem(): void
    {
        $entity = new PinbarTabStub();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('PinbarTab does not contain NavigationItem');

        $this->validator->validate($entity, $this->constraint);
    }

    public function testValidate(): void
    {
        $entity = new PinbarTabStub();

        $entity->setItem($navigationItem = $this->createNavigationItem());

        $this->pinbarTabUrlNormalizer
            ->expects(self::once())
            ->method('getNormalizedUrl')
            ->with(self::URL)
            ->willReturn(self::URL_NORMALIZED);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(self::PINBAR_TAB_CLASS_NAME)
            ->willReturn($pinbarTabRepository = $this->createMock(PinbarTabRepository::class));

        $pinbarTabRepository
            ->expects(self::once())
            ->method('countNavigationItems')
            ->with(
                self::URL_NORMALIZED,
                $navigationItem->getUser(),
                $navigationItem->getOrganization(),
                $navigationItem->getType()
            )
            ->willReturn(0);

        $this->validator->validate($entity, $this->constraint);
    }

    public function testValidateWhenAlreadyExists(): void
    {
        $entity = new PinbarTabStub();

        $entity->setItem($navigationItem = $this->createNavigationItem());

        $this->pinbarTabUrlNormalizer
            ->expects(self::once())
            ->method('getNormalizedUrl')
            ->with(self::URL)
            ->willReturn(self::URL_NORMALIZED);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(self::PINBAR_TAB_CLASS_NAME)
            ->willReturn($pinbarTabRepository = $this->createMock(PinbarTabRepository::class));

        $pinbarTabRepository
            ->expects(self::once())
            ->method('countNavigationItems')
            ->with(
                self::URL_NORMALIZED,
                $navigationItem->getUser(),
                $navigationItem->getOrganization(),
                $navigationItem->getType()
            )
            ->willReturn(1);

        $this->validator->initialize($context = $this->createMock(ExecutionContextInterface::class));

        $this->constraint->message = 'sample-message';

        $context
            ->expects(self::once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($builder = $this->createMock(ConstraintViolationBuilderInterface::class));

        $builder
            ->expects(self::once())
            ->method('addViolation');

        $this->validator->validate($entity, $this->constraint);
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
}
