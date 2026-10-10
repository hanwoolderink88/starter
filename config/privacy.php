<?php

/*
|--------------------------------------------------------------------------
| Privacy (GDPR) terms
|--------------------------------------------------------------------------
|
| How long personal data is kept. The scheduled commands `users:anonymize`
| and `activity:prune` (routes/console.php) apply these terms every day.
|
*/

return [

    /*
    | A deleted user can be restored for this many days. After that their
    | account and every trace of them in the activity log is anonymized.
    */

    'anonymize_deleted_users_after_days' => (int) env('PRIVACY_ANONYMIZE_DELETED_USERS_AFTER_DAYS', 30),

    /*
    | IP addresses, user agents and the email addresses typed into failed
    | logins are cleared from the activity log after this many days. The
    | entries themselves stay.
    */

    'activity_network_details_days' => (int) env('PRIVACY_ACTIVITY_NETWORK_DETAILS_DAYS', 90),

    /*
    | Activity log entries older than this many days are deleted. Null keeps
    | them forever; after the terms above they hold no personal data of
    | deleted users any more.
    */

    'activity_retention_days' => env('PRIVACY_ACTIVITY_RETENTION_DAYS') !== null
        ? (int) env('PRIVACY_ACTIVITY_RETENTION_DAYS')
        : null,

];
