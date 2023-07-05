<?php

namespace App\Console\Commands;

use App\Http\Controllers\WooCategory;
use App\Http\Controllers\WooProduct;
use Illuminate\Console\Command;
use Codexshaper\WooCommerce\Facades\Category;
use Codexshaper\WooCommerce\Facades\Product;
use OdooClient\Client;
use Codexshaper\WooCommerce\Facades\Variation;
use Codexshaper\WooCommerce\Facades\Attribute;
use App\Http\Controllers\WooAttribute;
use Codexshaper\WooCommerce\Facades\Term;
use Illuminate\Support\Facades\Mail;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'woo:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('OdooWoo Test Command - '.date("F j, Y, g:i a"));
    }

}
