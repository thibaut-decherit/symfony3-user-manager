<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Swift_Message;
use Symfony\Component\Templating\EngineInterface;
use Swift_Mailer;
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
     * @param User $user
     * @param string $activationUrl
     */
    public function loginAttemptOnNonActivatedAccount(User $user, string $activationUrl)
    {
        $emailBody = $this->twigEngine->render(
            'Email/login-attempt-on-non-activated-account.twig', [
                'user' => $user,
                'activationUrl' => $activationUrl
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
     * @param User $user
     * @param int $passwordResetTokenLifetimeInMinutes
     */
    public function passwordReset(User $user, int $passwordResetTokenLifetimeInMinutes)
    {
        $emailBody = $this->twigEngine->render(
            'Email/password-reset-email.html.twig', [
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
     * @param User $user
     */
    public function registrationAttemptOnExistingActivatedAccount(User $user)
    {
        $emailBody = $this->twigEngine->render(
            'Email/registration-attempt-on-existing-activated-account.twig', [
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
     * @param User $user
     */
    public function registrationAttemptOnExistingNonActivatedAccount(User $user)
    {
        $emailBody = $this->twigEngine->render(
            'Email/registration-attempt-on-existing-non-activated-account.twig', [
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
     * @param User $user
     */
    public function registrationSuccess(User $user)
    {
        $emailBody = $this->twigEngine->render(
            'Email/registration-success-email.html.twig', [
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
