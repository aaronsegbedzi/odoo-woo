<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => env('TIME_ZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => 'file',
        // 'store'  => 'redis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
    ])->toArray(),

    // OdooWoo Settings
    'odoowoo_company' => env('ODOOWOO_COMPANY_NAME', "COCOWARE"),
    'odoowoo_admin_email' => env('MAIL_NOTIFICATIONS','admin@cocoware.co.uk'),
    'odoowoo_sync_simple' => (bool) env('ODOOWOO_SYNC_SIMPLE_ENABLED', false),
    'odoowoo_sync_variable' => (bool) env('ODOOWOO_SYNC_VARIABLE_ENABLED', false),
    'odoowoo_cron' => (bool) env('ODOOWOO_CRON_DEBUG', false),
    
    // OdooWoo POS SMS Settings
    'odoowoo_pos_sms' => (bool) env('ODOOWOO_POS_SMS_NOTIFICATIONS', false),
    'odoowoo_pos_sms_recipients' => env('ODOOWOO_POS_SMS_NOTIFICATIONS_RECIPIENTS', "0558181935"),
    'odoowoo_pos_sms_time' => env('ODOOWOO_POS_SMS_NOTIFICATIONS_TIME', '18:00'),
    
    // OdooWoo Customer SMS Settings
    'odoowoo_customer_sms' => (bool) env('ODOOWOO_CUSTOMER_SMS_NOTIFICATIONS', false),
    'odoowoo_customer_sms_time' => (bool) env('ODOOWOO_CUSTOMER_SMS_NOTIFICATIONS_TIME', '12:00'),
    'odoowoo_customer_sms_template_1' => env('ODOOWOO_CUSTOMER_SMS_TEMPLATE', "Hey [name], it's [company]. Your recent purchase warms our hearts. Thank you for choosing us!"),
    
    // OdooWoo Currency Settings
    'odoowoo_currency' => env('ODOOWOO_CURRENCY', 'GHS'),

    // OdooWoo Pricelist Settings
    'odoowoo_pricelist' => (int) env('ODOO_PRICELIST_ID', 0),

    // WooCommerce Settings
    'woo_sleep_seconds' => env('WOOCOMMERCE_BATCH_WAIT_TIME', 10),
    'woo_products_per_batch' => env('WOOCOMMERCE_PRODUCTS_PER_BATCH', 10),
    'woo_default_desc' => (bool) env('WOOCOMMERCE_DEFAULT_DESCRIPTION', false),

    // Odoo Settings
    'odoo_url' => env('ODOO_URL', ''),
    'odoo_db' => env('ODOO_DB', ''),
    'odoo_username' => env('ODOO_USERNAME', ''),
    'odoo_password' => env('ODOO_PASSWORD', ''),
    'odoo_delay' => env('ODOO_DELAY_SECONDS', 1),

    // mNotify Settings
    'mnotify_api_url' => env('MNOTIFY_API_URL', ''),
    'mnotify_api_key' => env('MNOTIFY_API_KEY', ''),
    'mnotify_sender_id' => env('MNOTIFY_SENDER_ID', 'COCOWARE'),
    
    // myCred WooCommerce Plugin Settings
    'mycred_enabled' => (bool) env('MYCRED_PLUGIN_ENABLED', false),
    'mycred_default_points' => env('MYCRED_DEFAULT_POINTS', 10),


];
