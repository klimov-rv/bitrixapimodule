<?php

declare(strict_types=1);

return [
    'settings' => [
        'displayErrorDetails'               => true,
        'addContentLengthHeader'            => false, // Allow the web server to send the content-length header
        'determineRouteBeforeAppMiddleware' => true,
    ],
];
