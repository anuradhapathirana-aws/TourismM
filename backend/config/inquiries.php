<?php

/*
| Custom tour inquiries ("Build your own journey" on the website).
| Option keys are the values the website sends. Keep them equal to
| frontend/src/components/journey/journeyOptions.js. Labels are what the admin shows.
| Adding an option = add it here and in journeyOptions.js. Removing one makes old values show as the raw key.
*/

return [
    // Request limits (validation)
    'max_guests_per_type' => 30,   // men / women / children each
    'max_nights' => 90,
    'book_ahead_years' => 2,       // arrival at most this far ahead
    'max_destinations' => 30,
    'max_activities' => 30,
    'notes_max' => 3000,

    // Public POST /journey-requests: per IP
    'rate_limit' => ['per_minute' => 5, 'per_day' => 20],

    'airports' => [
        'cmb' => 'Bandaranaike International, Colombo (CMB)',
        'hri' => 'Mattala Rajapaksa International (HRI)',
        'in-country' => 'Already in Sri Lanka',
        'not-booked' => 'Flights not booked yet',
    ],

    'occasions' => [
        'holiday' => 'Holiday',
        'honeymoon' => 'Honeymoon',
        'family' => 'Family trip',
        'anniversary' => 'Anniversary',
        'birthday' => 'Birthday',
        'friends' => 'Trip with friends',
        'solo' => 'Solo travel',
        'business' => 'Business + leisure',
        'other' => 'Other',
    ],

    'accommodation' => [
        'budget' => 'Budget · 2–3★ & guesthouses',
        'comfort' => 'Comfort · 3–4★',
        'luxury' => 'Luxury · 5★',
        'boutique' => 'Boutique hotels & villas',
        'mixed' => 'Mix it up',
        'none' => 'Not needed (own booking)',
    ],

    'meal_plans' => [
        'bb' => 'Breakfast only',
        'hb' => 'Half board',
        'fb' => 'Full board',
        'none' => 'No meals',
    ],

    'vehicles' => [
        'recommend' => 'Recommend one',
        'car' => 'Car (up to 3)',
        'van' => 'Van (up to 8)',
        'luxury-suv' => 'Luxury SUV',
        'coach' => 'Mini-coach',
    ],

    // Per person, land tour only. The currency comes from config('tours.currency').
    'budgets' => [
        'under-500' => 'Under 500',
        '500-1000' => '500 – 1,000',
        '1000-2000' => '1,000 – 2,000',
        '2000-3500' => '2,000 – 3,500',
        '3500-plus' => '3,500+',
    ],

    'contact_methods' => [
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'phone' => 'Phone call',
    ],

    'guide_languages' => ['English', 'German', 'French', 'Spanish', 'Italian', 'Russian', 'Chinese', 'Japanese', 'Hindi', 'Arabic', 'Other'],

    'referrals' => ['Google search', 'Instagram', 'Facebook', 'TikTok', 'TripAdvisor', 'Friend or family', 'Travelled with you before', 'Other'],
];
