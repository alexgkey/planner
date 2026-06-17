<?php

namespace App\EventSubscriber;

use App\Audit\AuditAction;
use App\Audit\AuditLogger;
use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class AuthAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $this->auditLogger->log(
            AuditAction::AUTH_LOGIN_SUCCESS,
            $user,
            'auth_session',
            null,
            $user->getEmail(),
            null,
            [
                'firewall' => $event->getFirewallName(),
            ]
        );
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $email = $request->request->getString('_username');

        $this->auditLogger->log(
            AuditAction::AUTH_LOGIN_FAILURE,
            null,
            'auth_session',
            null,
            '' !== $email ? $email : 'login',
            null,
            [
                'firewall' => $event->getFirewallName(),
                'error' => $event->getException()->getMessageKey(),
            ],
            '' !== $email ? $email : null
        );
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        if (!$user instanceof User) {
            return;
        }

        $this->auditLogger->log(
            AuditAction::AUTH_LOGOUT,
            $user,
            'auth_session',
            null,
            $user->getEmail()
        );
    }
}
