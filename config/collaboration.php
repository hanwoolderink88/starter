<?php

/*
|--------------------------------------------------------------------------
| Working together on fields
|--------------------------------------------------------------------------
|
| One person edits a field at a time. A session ends when they leave the
| field, when their browser stops sending a heartbeat, or after a while
| without typing; `collaboration:close-expired-sessions` (every minute)
| closes the sessions that ended without a goodbye.
|
*/

return [

    // How often an open editor tells the server it is still there.
    'heartbeat_seconds' => (int) env('COLLABORATION_HEARTBEAT_SECONDS', 15),

    // How long a field stays held after the last heartbeat (a closed laptop).
    'connection_grace_seconds' => (int) env('COLLABORATION_CONNECTION_GRACE_SECONDS', 45),

    // How long a field stays held without typing.
    'idle_seconds' => (int) env('COLLABORATION_IDLE_SECONDS', 120),

    // The longest value a collaborative field accepts.
    'max_length' => (int) env('COLLABORATION_MAX_LENGTH', 50000),

];
