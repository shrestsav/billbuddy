<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1.000000],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 0.920000],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'exchange_rate' => 0.790000],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'exchange_rate' => 149.500000],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'C$', 'exchange_rate' => 1.360000],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'exchange_rate' => 1.530000],
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'Fr', 'exchange_rate' => 0.880000],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'exchange_rate' => 7.240000],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'exchange_rate' => 83.200000],
            ['code' => 'MXN', 'name' => 'Mexican Peso', 'symbol' => '$', 'exchange_rate' => 17.150000],
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$', 'exchange_rate' => 4.970000],
            ['code' => 'KRW', 'name' => 'South Korean Won', 'symbol' => '₩', 'exchange_rate' => 1320.000000],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'exchange_rate' => 1.340000],
            ['code' => 'HKD', 'name' => 'Hong Kong Dollar', 'symbol' => 'HK$', 'exchange_rate' => 7.820000],
            ['code' => 'NOK', 'name' => 'Norwegian Krone', 'symbol' => 'kr', 'exchange_rate' => 10.650000],
            ['code' => 'SEK', 'name' => 'Swedish Krona', 'symbol' => 'kr', 'exchange_rate' => 10.420000],
            ['code' => 'DKK', 'name' => 'Danish Krone', 'symbol' => 'kr', 'exchange_rate' => 6.870000],
            ['code' => 'NZD', 'name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'exchange_rate' => 1.640000],
            ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R', 'exchange_rate' => 18.650000],
            ['code' => 'RUB', 'name' => 'Russian Ruble', 'symbol' => '₽', 'exchange_rate' => 89.500000],
            ['code' => 'TRY', 'name' => 'Turkish Lira', 'symbol' => '₺', 'exchange_rate' => 30.250000],
            ['code' => 'PLN', 'name' => 'Polish Zloty', 'symbol' => 'zł', 'exchange_rate' => 3.980000],
            ['code' => 'THB', 'name' => 'Thai Baht', 'symbol' => '฿', 'exchange_rate' => 35.400000],
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'exchange_rate' => 15680.000000],
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'exchange_rate' => 4.720000],
            ['code' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => '₱', 'exchange_rate' => 55.800000],
            ['code' => 'CZK', 'name' => 'Czech Koruna', 'symbol' => 'Kč', 'exchange_rate' => 22.850000],
            ['code' => 'ILS', 'name' => 'Israeli Shekel', 'symbol' => '₪', 'exchange_rate' => 3.680000],
            ['code' => 'CLP', 'name' => 'Chilean Peso', 'symbol' => '$', 'exchange_rate' => 878.000000],
            ['code' => 'PKR', 'name' => 'Pakistani Rupee', 'symbol' => '₨', 'exchange_rate' => 279.500000],
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'د.إ', 'exchange_rate' => 3.670000],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => '﷼', 'exchange_rate' => 3.750000],
            ['code' => 'EGP', 'name' => 'Egyptian Pound', 'symbol' => 'E£', 'exchange_rate' => 30.900000],
            ['code' => 'VND', 'name' => 'Vietnamese Dong', 'symbol' => '₫', 'exchange_rate' => 24350.000000],
            ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳', 'exchange_rate' => 109.800000],
            ['code' => 'HUF', 'name' => 'Hungarian Forint', 'symbol' => 'Ft', 'exchange_rate' => 356.000000],
            ['code' => 'RON', 'name' => 'Romanian Leu', 'symbol' => 'lei', 'exchange_rate' => 4.580000],
            ['code' => 'BGN', 'name' => 'Bulgarian Lev', 'symbol' => 'лв', 'exchange_rate' => 1.800000],
            ['code' => 'HRK', 'name' => 'Croatian Kuna', 'symbol' => 'kn', 'exchange_rate' => 6.930000],
            ['code' => 'UAH', 'name' => 'Ukrainian Hryvnia', 'symbol' => '₴', 'exchange_rate' => 37.500000],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}
