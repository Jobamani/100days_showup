<?php

return [
    'slow_query_ms' => (float) env('SLOW_QUERY_MS', 100),
    'expose_timing' => (bool) env('EXPOSE_SERVER_TIMING', env('APP_ENV') !== 'production'),
];