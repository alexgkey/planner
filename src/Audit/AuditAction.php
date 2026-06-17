<?php

namespace App\Audit;

final class AuditAction
{
    public const EVENT_CREATED = 'event.created';
    public const EVENT_UPDATED = 'event.updated';
    public const EVENT_CANCELLED = 'event.cancelled';
    public const EVENT_RESTORED = 'event.restored';
    public const EVENT_DELETED = 'event.deleted';
    public const EVENT_DUPLICATED = 'event.duplicated';
    public const EVENT_EXPORTED_XLS = 'event.exported_xls';
    public const EVENT_EXPORTED_PDF = 'event.exported_pdf';
    public const EVENT_EXPORTED_REPORTS_PDF = 'event.exported_reports_pdf';
    public const EVENT_EXPORTED_PHOTOS = 'event.exported_photos';

    public const EVENT_REPORT_CREATED = 'event_report.created';
    public const EVENT_REPORT_UPDATED = 'event_report.updated';
    public const EVENT_REPORT_PHOTO_ADDED = 'event_report.photo_added';
    public const EVENT_REPORT_PHOTO_DELETED = 'event_report.photo_deleted';
    public const EVENT_REPORT_SCENARIO_DOWNLOADED = 'event_report.scenario_downloaded';

    public const AUTH_LOGIN_SUCCESS = 'auth.login_success';
    public const AUTH_LOGIN_FAILURE = 'auth.login_failure';
    public const AUTH_LOGOUT = 'auth.logout';

    private function __construct()
    {
    }
}
