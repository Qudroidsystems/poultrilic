<?php

return [
    // Let people create their own account on the sign-up page (they get no role until an admin assigns one).
    'allow_registration' => (bool) env('ALLOW_REGISTRATION', true),
];
