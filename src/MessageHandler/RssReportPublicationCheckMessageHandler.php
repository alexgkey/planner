<?php

namespace App\MessageHandler;

use App\Message\RssReportPublicationCheckMessage;
use App\Service\RssReportPublicationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RssReportPublicationCheckMessageHandler
{
    public function __construct(private readonly RssReportPublicationService $rssReportPublicationService)
    {
    }

    public function __invoke(RssReportPublicationCheckMessage $message): void
    {
        $this->rssReportPublicationService->publishDailyReports();
    }
}
