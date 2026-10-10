<?php

use Illuminate\Support\Facades\Schedule;

// Privacy terms (config/privacy.php).
Schedule::command('users:anonymize')->daily()->withoutOverlapping();
Schedule::command('activity:prune')->daily()->withoutOverlapping();
