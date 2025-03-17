<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Transforms between EmailOrigin entity and its ID.
 */
class OriginTransformer implements DataTransformerInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private TokenAccessorInterface $tokenAccessor,
        private EmailOriginHelper $emailOriginHelper
    ) {
    }

    #[\Override]
    public function transform($value)
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof EmailOrigin) {
            throw new UnexpectedTypeException($value, EmailOrigin::class);
        }

        return $value->getId();
    }

    #[\Override]
    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }

        [$id, $email] = array_pad(explode('|', $value), 2, null);
        if (!$id) {
            $origin = $this->findByOwner($this->tokenAccessor->getUser());
            if (!$origin) {
                $origin = $this->createInternalOrigin($email);
            }

            return $origin;
        }

        return $this->loadEntityById((int)$id);
    }

    private function loadEntityById(int $id): EmailOrigin
    {
        $result = $this->doctrine->getManagerForClass(EmailOrigin::class)->find(EmailOrigin::class, $id);
        if (!$result) {
            throw new TransformationFailedException(\sprintf('The value "%s" does not exist or not unique.', $id));
        }

        return $result;
    }

    private function findByOwner(object $owner): ?EmailOrigin
    {
        return $this->doctrine->getRepository(EmailOrigin::class)->findOneBy(['owner' => $owner]);
    }

    private function createInternalOrigin(string $email): EmailOrigin
    {
        return $this->emailOriginHelper->getEmailOrigin($email);
    }
}
