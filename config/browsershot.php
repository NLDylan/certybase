<?php

return [
    'node_path' => env('BROWSERSHOT_NODE_PATH'),
    'npm_path' => env('BROWSERSHOT_NPM_PATH'),
    'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
    'timeout' => env('BROWSERSHOT_TIMEOUT', 120),
    'arguments' => array_filter([
        env('BROWSERSHOT_CHROMIUM_NO_SANDBOX') ? '--no-sandbox' : null,
        env('BROWSERSHOT_HIDE_SCROLLBARS') ? '--hide-scrollbars' : null,
    ]),
];
