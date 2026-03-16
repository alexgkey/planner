<?php

namespace App\Service;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class EventReportReminderService
{
    private readonly \DateTimeZone $timezoneObject;

    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%env(int:EVENT_REPORT_REMINDER_HOUR)%')]
        private readonly int $hour,
        #[Autowire('%env(int:EVENT_REPORT_REMINDER_MINUTE)%')]
        private readonly int $minute,
        #[Autowire('%env(int:EVENT_REPORT_REMINDER_DAYS)%')]
        private readonly int $windowDays,
        #[Autowire('%env(string:EVENT_REPORT_REMINDER_TIMEZONE)%')]
        private readonly string $timezone,
        #[Autowire('%env(string:EVENT_REPORT_REMINDER_FROM_EMAIL)%')]
        private readonly string $fromEmail,
        #[Autowire('%env(string:EVENT_REPORT_REMINDER_FROM_NAME)%')]
        private readonly string $fromName,
    ) {
        $this->timezoneObject = new \DateTimeZone($this->timezone);
    }

    public function sendPendingReminders(?\DateTimeImmutable $runAt = null): int
    {
        $runAt = $runAt?->setTimezone($this->timezoneObject) ?? new \DateTimeImmutable('now', $this->timezoneObject);
        $events = $this->eventRepository->findEventsNeedingReportReminder($runAt, $this->windowDays);
        $sentCount = 0;

        foreach ($events as $event) {
            if (!$this->shouldSendReminder($event, $runAt)) {
                continue;
            }

            $creator = $event->getCreator();
            if (null === $creator || !$creator->isActive()) {
                continue;
            }

            $recipientEmail = trim((string) $creator->getEmail());
            if ('' === $recipientEmail) {
                continue;
            }

            $this->mailer->send(
                (new TemplatedEmail())
                    ->from(new Address($this->fromEmail, $this->fromName))
                    ->to($recipientEmail)
                    ->subject(sprintf('Напоминание: заполните отчет по мероприятию "%s"', $event->getTitle()))
                    ->htmlTemplate('emails/event_report_reminder.html.twig')
                    ->context([
                        'event' => $event,
                        'employee' => $creator->getEmployee(),
                        'show_url' => $this->urlGenerator->generate('app_event_show', ['id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                        'report_url' => $this->urlGenerator->generate('app_event_report', ['id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                        'reminder_day_number' => $event->getReportReminderSentCount() + 1,
                        'window_days' => $this->windowDays,
                    ])
            );

            $event
                ->setReportReminderLastSentAt($runAt)
                ->setReportReminderSentCount($event->getReportReminderSentCount() + 1);

            ++$sentCount;
        }

        if ($sentCount > 0) {
            $this->entityManager->flush();
        }

        return $sentCount;
    }

    private function shouldSendReminder(Event $event, \DateTimeImmutable $runAt): bool
    {
        $reminderStartAt = $this->getReminderStartAt($event);
        if (null === $reminderStartAt || $runAt < $reminderStartAt) {
            return false;
        }

        $daysSinceReminderStart = $reminderStartAt->setTime(0, 0)->diff($runAt->setTime(0, 0))->days;

        return $daysSinceReminderStart < $this->windowDays;
    }

    private function getReminderStartAt(Event $event): ?\DateTimeImmutable
    {
        $eventDate = $event->getDate();
        if (null === $eventDate) {
            return null;
        }

        $date = \DateTimeImmutable::createFromInterface($eventDate)->setTimezone($this->timezoneObject)->setTime(0, 0);
        $eventTime = $event->getTime();

        if (null === $eventTime) {
            return $date->setTime($this->hour, $this->minute);
        }

        return $date->setTime(
            (int) $eventTime->format('H'),
            (int) $eventTime->format('i')
        );
    }
}
