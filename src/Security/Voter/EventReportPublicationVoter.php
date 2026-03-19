<?php

namespace App\Security\Voter;

use App\Entity\EventReportPublication;
use App\Entity\User;
use App\Security\Permissions\AppPermissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EventReportPublicationVoter extends Voter
{
    public const VIEW = 'EVENT_REPORT_PUBLICATION_VIEW';
    public const MANAGE = 'EVENT_REPORT_PUBLICATION_MANAGE';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof EventReportPublication) {
            return false;
        }

        return in_array($attribute, [self::VIEW, self::MANAGE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->security->isGranted(AppPermissions::EVENT_REPORT_VIEW)
                || $this->security->isGranted(AppPermissions::EVENT_REPORT_MANAGE),
            self::MANAGE => $this->security->isGranted(AppPermissions::EVENT_REPORT_MANAGE),
            default => false,
        };
    }
}