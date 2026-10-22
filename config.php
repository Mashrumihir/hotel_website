<?php
ob_start();

// HOTEL EASE - Configuration & Global Setup
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirect($url) {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    } else {
        echo '<script>window.location.href=' . json_encode($url) . ';</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url) . '"></noscript>';
        exit;
    }
}

// Brand Settings
define('SITE_NAME', $_SESSION['system_settings']['site_name'] ?? 'Hotel Ease');
define('SITE_TAGLINE', $_SESSION['system_settings']['site_tagline'] ?? 'Luxury Stay. Effortless Experience.');
define('SITE_EMAIL', $_SESSION['system_settings']['site_email'] ?? 'concierge@hotelease.com');
define('SITE_PHONE', $_SESSION['system_settings']['site_phone'] ?? '+1 (800) 555-EASE');
define('SITE_ADDRESS', $_SESSION['system_settings']['site_address'] ?? '777 Ocean Drive, Paradise Bay, CA 90210');
define('CHECKIN_TIME', $_SESSION['system_settings']['checkin_time'] ?? '14:00');
define('CHECKOUT_TIME', $_SESSION['system_settings']['checkout_time'] ?? '11:00');
define('SITE_MAP_IFRAME', $_SESSION['system_settings']['site_map_iframe'] ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3691.828858349258!2d70.78161747589997!3d22.28448884347712!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3959ca248c89f091%3A0x6a568b209d6f3b0!2sRajkot%2C%20Gujarat!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin');

// SMTP Mailer Settings. Configure these in the server environment or Admin Settings.
define('SMTP_HOST', getenv('HOTEL_SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int) (getenv('HOTEL_SMTP_PORT') ?: 587));
define('SMTP_USER', getenv('HOTEL_SMTP_USER') ?: '');
define('SMTP_PASS', getenv('HOTEL_SMTP_PASS') ?: '');
define('SMTP_FROM_NAME', getenv('HOTEL_SMTP_FROM_NAME') ?: 'Hotel Ease');

// Database Settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hotel_ease');

// Color System Constants
define('COLOR_DEEP_NAVY', '#0B1F3A');
define('COLOR_ROYAL_BLUE', '#2563EB');
define('COLOR_SKY_BLUE', '#38BDF8');
define('COLOR_LIGHT_BG', '#F5F8FC');
define('COLOR_GOLD', '#C9A227');

// Helper Functions
function get_current_language() {
    if (isset($_SESSION['pref_language']) && !empty($_SESSION['pref_language'])) {
        return $_SESSION['pref_language'];
    }
    if (isset($_COOKIE['hotel_ease_language']) && !empty($_COOKIE['hotel_ease_language'])) {
        return $_COOKIE['hotel_ease_language'];
    }
    return 'en';
}

function get_current_currency() {
    if (isset($_SESSION['pref_currency']) && !empty($_SESSION['pref_currency'])) {
        return $_SESSION['pref_currency'];
    }
    if (isset($_COOKIE['hotel_ease_currency']) && !empty($_COOKIE['hotel_ease_currency'])) {
        return $_COOKIE['hotel_ease_currency'];
    }
    return 'INR';
}

function __($text) {
    static $langData = null;
    if ($langData === null) {
        $lang = get_current_language();
        $langData = get_language_dictionary($lang);
    }
    return $langData[$text] ?? $text;
}

function get_language_dictionary($lang) {
    $dicts = [
        'hi' => [
            'Dashboard' => 'डैशबोर्ड',
            'Rooms' => 'कमरे',
            'Browse Rooms' => 'कमरे खोजें',
            'My Bookings' => 'मेरी बुकिंग',
            'Upcoming Stays' => 'आगामी प्रवास',
            'Favorites' => 'पसंदीदा',
            'Help & Support' => 'सहायता एवं सपोर्ट',
            'Settings' => 'सेटिंग्स',
            'Logout' => 'लॉग आउट',
            'Account Settings' => 'खाता सेटिंग्स',
            'Search Rooms' => 'कमरे खोजें',
            'Book Now' => 'अभी बुक करें',
            'View Details' => 'विवरण देखें',
            'Photos' => 'फ़ोटो',
            'Select Currency' => 'मुद्रा चुनें',
            'Select Language' => 'भाषा चुनें',
            'Primary Guest Details' => 'प्राथमिक अतिथि विवरण',
            'Stay Dates & Occupancy' => 'रहने की तिथियां और उपस्थिति',
            'Pricing Breakdown' => 'मूल्य निर्धारण विवरण',
            'Full Name' => 'पूरा नाम',
            'Phone Number' => 'फ़ोन नंबर',
            'Age (Yrs)' => 'उम्र (वर्ष)',
            'ID Proof' => 'पहचान पत्र',
            'Check-In Date' => 'चेक-इन तिथि',
            'Check-Out Date' => 'चेक-आउट तिथि',
            'Adults' => 'वयस्क',
            'Children' => 'बच्चे',
            'Accompanying Guests' => 'साथी अतिथि विवरण',
            'Confirm Reservation & Proceed to Payment' => 'आरक्षण की पुष्टि करें और भुगतान करें'
        ],
        'es' => [
            'Dashboard' => 'Panel Principal',
            'Rooms' => 'Habitaciones',
            'Browse Rooms' => 'Explorar Habitaciones',
            'My Bookings' => 'Mis Reservas',
            'Upcoming Stays' => 'Próximas Estancias',
            'Favorites' => 'Favoritos',
            'Help & Support' => 'Ayuda y Soporte',
            'Settings' => 'Configuración',
            'Logout' => 'Cerrar Sesión',
            'Account Settings' => 'Configuración de la cuenta'
        ],
        'fr' => [
            'Dashboard' => 'Tableau de Bord',
            'Rooms' => 'Chambres',
            'Browse Rooms' => 'Parcourir les Chambres',
            'My Bookings' => 'Mes Réservations',
            'Upcoming Stays' => 'Séjours à Venir',
            'Favorites' => 'Favoris',
            'Help & Support' => 'Aide et Support',
            'Settings' => 'Paramètres',
            'Logout' => 'Déconnexion'
        ],
        'de' => [
            'Dashboard' => 'Übersicht',
            'Rooms' => 'Zimmer',
            'Browse Rooms' => 'Zimmer Durchsuchen',
            'My Bookings' => 'Meine Buchungen',
            'Upcoming Stays' => 'Anstehende Aufenthalte',
            'Favorites' => 'Favoriten',
            'Help & Support' => 'Hilfe & Support',
            'Settings' => 'Einstellungen',
            'Logout' => 'Abmelden'
        ]
    ];
    return $dicts[$lang] ?? [];
}

function format_price($amount, $targetCurrency = null, $asHtml = true) {
    $curr = $targetCurrency ?? get_current_currency();
    $amount = (float)$amount;

    $currencies = [
        'INR' => ['symbol' => '₹', 'rate' => 1.0],
        'USD' => ['symbol' => '$', 'rate' => 0.012],
        'EUR' => ['symbol' => '€', 'rate' => 0.011],
        'GBP' => ['symbol' => '£', 'rate' => 0.0095],
        'AED' => ['symbol' => 'AED ', 'rate' => 0.044]
    ];

    $info = $currencies[$curr] ?? $currencies['INR'];
    $converted = $amount * $info['rate'];
    $formattedNum = number_format($converted, 2);

    if (!$asHtml) {
        return $info['symbol'] . $formattedNum;
    }

    return '<span class="price-amount font-sans"><span class="currency-symbol">' . htmlspecialchars($info['symbol']) . '</span>' . $formattedNum . '</span>';
}

function format_price_text($amount, $targetCurrency = null) {
    return format_price($amount, $targetCurrency, false);
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function calculate_nights($check_in, $check_out) {
    $d1 = new DateTime($check_in);
    $d2 = new DateTime($check_out);
    $interval = $d1->diff($d2);
    return max(1, $interval->days);
}

function set_toast($message, $type = 'success') {
    $_SESSION['toast'] = [
        'message' => $message,
        'type' => $type
    ];
}
?>
