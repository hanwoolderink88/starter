<?php

declare(strict_types=1);

return [
    'locked_by' => ':name is editing this.',
    'locked_other_tab' => 'You are editing this in another tab.',
    'not_held' => 'You are no longer editing this field.',

    'field' => [
        'editing_by' => ':name is editing',
        'editing_by_impersonated' => ':name (impersonated by :impersonator) is editing',
        'editing_via_claude' => ':name is editing via Claude',
        'other_tab' => 'You are editing this in another tab',
        'saving' => 'Saving…',
        'saved' => 'Saved',
        'unsaved' => 'Not saved',
        'offline' => 'Connection lost. Your text is kept and saved once you are back online.',
        'expired' => 'Your editing session ended after a while without typing. Your text was saved.',
        'continue' => 'Continue editing',
        'claim_failed' => 'This field could not be opened for editing.',
        'conflict_fallback' => 'Someone else changed this field while you were offline.',
        'history' => 'History',
    ],

    'revisions' => [
        'title' => 'History of :field',
        'description' => 'Every editing session of this text. Restoring adds a new version; nothing is lost.',
        'empty' => 'No earlier versions yet.',
        'number' => 'Version :number',
        'restored_from' => 'restored from version :number',
        'by' => 'by :name',
        'via_claude' => 'via Claude',
        'impersonated_by' => 'impersonated by :name',
        'changes' => 'Changes up to the current text',
        'no_changes' => 'Same as the current text.',
        'content' => 'Text of this version',
        'restore' => 'Restore this version',
        'restore_locked' => 'Someone is editing this field; restore once they are done.',
        'restored' => 'Version :number restored.',
        'load_failed' => 'The history could not be loaded.',
        'additions' => '+:count',
        'deletions' => '−:count',
    ],
];
