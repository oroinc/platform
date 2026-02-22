<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Twig;

use Oro\Bundle\UserBundle\Model\Gender;
use Oro\Bundle\UserBundle\Provider\GenderProvider;
use Oro\Bundle\UserBundle\Twig\UserExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private GenderProvider&MockObject $genderProvider;
    private UserExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->genderProvider = $this->createMock(GenderProvider::class);

        $container = self::getContainerBuilder()
            ->add(GenderProvider::class, $this->genderProvider)
            ->getContainer($this);

        $this->extension = new UserExtension($container);
    }

    public function testGetGenderLabel(): void
    {
        $label = 'Male';

        $this->genderProvider->expects(self::once())
            ->method('getLabelByName')
            ->with(Gender::MALE)
            ->willReturn($label);

        self::assertNull(
            self::callTwigFunction($this->extension, 'oro_gender', [null])
        );
        self::assertEquals(
            $label,
            self::callTwigFunction($this->extension, 'oro_gender', [Gender::MALE])
        );
    }
}
