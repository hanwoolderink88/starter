<?php

use Illuminate\Support\Facades\Schedule;

// Privacy terms (config/privacy.php).
Schedule::command('users:anonymize')->daily()->withoutOverlapping();
Schedule::command('activity:prune')->daily()->withoutOverlapping();

// Editing sessions that ended without a goodbye (config/collaboration.php).
Schedule::command('collaboration:close-expired-sessions')->everyMinute()->withoutOverlapping();
