<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\OpenMarketplace\Api\Security\Voter;

use BitBag\OpenMarketplace\Api\Context\VendorContextInterface;
use BitBag\OpenMarketplace\Entity\VendorAwareInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class VendorAwareVoter extends Voter
{
    private VendorContextInterface $vendorContext;

    public function __construct(VendorContextInterface $vendorContext)
    {
        $this->vendorContext = $vendorContext;
    }

    protected function supports(string $attribute, $subject): bool
    {
        $supportsAttribute = in_array($attribute, ['VENDOR_AWARE_OBJECT_CREATE']);

        $supportsSubject = $subject instanceof VendorAwareInterface;

        return $supportsAttribute && $supportsSubject;
    }

    /**
     * @param VendorAwareInterface $subject
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if (
            'VENDOR_AWARE_OBJECT_CREATE' === $attribute &&
            null === $this->vendorContext->getVendor()
        ) {
            return false;
        }

        return true;
    }
}

