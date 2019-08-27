<?php

namespace AppBundle\Service;

use AppBundle\Model\AbstractUser;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class MailerService
 * @package AppBundle\Service
 */
class MailerService
{
    /**
     * @var string
     */
    private $autoMailerAddress;

    /**
     * @var string
     */
    private $replyTo;

    /**
     * @var EngineInterface
     */
    private $twigEngine;

    /**
     * @var Swift_Mailer
     */
    private $swiftMailer;

    /**
     * @var TranslatorInterface
     */
    private $translatorInterface;

    /**
     * MailerService constructor.
     * @param string $autoMailerAddress
     * @param string $replyTo
     * @param EngineInterface $twigEngine
     * @param Swift_Mailer $swiftMailer
     * @param TranslatorInterface $translatorInterface
     */
    public function __construct(
        string $autoMailerAddress,
        string $replyTo,
        EngineInterface $twigEngine,
        Swift_Mailer $swiftMailer,
        TranslatorInterface $translatorInterface
    )
    {
        $this->autoMailerAddress = $autoMailerAddress;
        $this->replyTo = $replyTo;
        $this->twigEngine = $twigEngine;
        $this->swiftMailer = $swiftMailer;
        $this->translatorInterface = $translatorInterface;
    }

    /**
     * Email sent when user requests account deletion.
     *
     * @param AbstractUser $user
     * @param int $accountDeletionTokenLifetimeInMinutes
     */
    public function accountDeletionRequest(AbstractUser $user, int $accountDeletionTokenLifetimeInMinutes)
    {
        $emailBody = $this->twigEngine->render(
            'Email/User/account-deletion-request.html.twig', [
                'user' => $user,
                'accountDeletionTokenLifetimeInMinutes' => $accountDeletionTokenLifetimeInMinutes
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.account_deletion_request'),
            [$this->autoMailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyTo,
            $emailBody
        );
    }

    /**
     * Email sent when user requests account deletion.
     *
     * @param AbstractUser $user
     */
    public function accountDeletionSuccess(AbstractUser $user)
    {
        $emailBody = $this->twigEngine->render(
            'Email/User/account-deletion-success.html.twig', [
                'user' => $user,
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.account_deletion_success'),
            [$this->autoMailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyTo,
            $emailBody
        );
    }

    /**
     * Email sent when user requests email address change.
     *
     * @param AbstractUser $user
     * @param int $emailChangeTokenLifetimeInMinutes
     */
    public function emailChange(AbstractUser $user, int $emailChangeTokenLifetimeInMinutes)
    {
        $emailBody = $this->twigEngine->render(
            'Email/User/email-address-change.html.twig', [
                'user' => $user,
                'emailChangeTokenLifetimeInMinutes' => $emailChangeTokenLifetimeInMinutes
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.email_address_change'),
            [$this->autoMailerAddress => 'UserManager'],
            $user->getEmailChangePending(),
            $this->replyTo,
            $emailBody
        );
    }

    /**
     * @param AbstractUser $user
     */
    public function loginAttemptOnNonActivatedAccount(AbstractUser $user)
    {
        $emailBody = $this->twigEngine->render(
            'Email/User/login-attempt-on-non-activated-account.html.twig', [
                'user' => $user
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.login_attempt'),
            [$this->autoMailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyTo,
            $emailBody
        );
    }

    /**
     * Email sent when user requests password reset.
     *
     * @param AbstractUser $user
     * @param int $passwordResetTokenLifetimeInMinutes
     */
    public function passwordResetRequest(AbstractUser $user, int $passwordResetTokenLifetimeInMinutes)
    {
        $emailBody = $this->twigEngine->render(
            'Email/User/password-reset-request.html.twig', [
                'user' => $user,
                'passwordResetTokenLifetimeInMinutes' => $passwordResetTokenLifetimeInMinutes
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.password_reset'),
            [$this->autoMailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyTo,
            $emailBody
        );
    }

    /**
     * @param AbstractUser $user
     */
    public function registrationAttemptOnExistingVerifiedEmailAddress(AbstractUser $user)
    {
        $emailBody = $this->twigEngine->render(
            'Email/User/registration-attempt-on-existing-verified-email-address.html.twig', [
                'user' => $user
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.registration_attempt'),
            [$this->autoMailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyTo,
            $emailBody
        );
    }

    /**
     * @param AbstractUser $user
     */
    public function registrationAttemptOnExistingUnverifiedEmailAddress(AbstractUser $user)
    {
        $emailBody = $this->twigEngine->render(
            'Email/User/registration-attempt-on-existing-unverified-email-address.html.twig', [
                'user' => $user
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.registration_attempt'),
            [$this->autoMailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyTo,
            $emailBody
        );
    }

    /**
     * Email sent after user registration.
     *
     * @param AbstractUser $user
     */
    public function registrationSuccess(AbstractUser $user)
    {
        $emailBody = $this->twigEngine->render(
            'Email/User/registration-success.html.twig', [
                'user' => $user
            ]
        );

        $this->sendEmail(
            $this->translatorInterface->trans('mailer.subjects.welcome'),
            [$this->autoMailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyTo,
            $emailBody
        );
    }

    /**
     * @param $subject
     * @param $from
     * @param $to
     * @param $replyTo
     * @param $body
     * @param null $attachment
     */
    private function sendEmail($subject, $from, $to, $replyTo, $body, $attachment = null)
    {
        $message = Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setReplyTo($replyTo)
            ->setBody($body, 'text/html');
        if ($attachment) {
            $message->attach($attachment);
        }

        $this->swiftMailer->send($message);
    }
}
