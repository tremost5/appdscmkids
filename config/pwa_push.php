<?php

return [
    'enabled' => env('PWA_PUSH_ENABLED', true),
    'subject' => env('PWA_PUSH_SUBJECT', 'mailto:helper@dscmkids.online'),
    'public_key' => env('PWA_PUSH_PUBLIC_KEY', ''),
    'private_key' => env('PWA_PUSH_PRIVATE_KEY', ''),
    'target_role_ids' => array_values(array_filter(array_map(
        static fn ($value) => (int) trim($value),
        explode(',', (string) env('PWA_PUSH_TARGET_ROLE_IDS', '3'))
    ))),
];
