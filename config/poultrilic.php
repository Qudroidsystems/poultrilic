<?php

return [
    // Let people create their own account on the sign-up page (they get no role until an admin assigns one).
    'allow_registration' => (bool) env('ALLOW_REGISTRATION', true),

    // The farm's name when none is saved in Prices & Costs › Business details. "Poultrilic" is the
    // software; the farm is Prime Farm.
    'farm_name' => env('FARM_NAME', 'Prime Farm'),
];
