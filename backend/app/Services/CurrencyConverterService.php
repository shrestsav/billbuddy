<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;

class CurrencyConverterService
{
    private const CACHE_TTL = 3600;

    public function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $fromRate = $this->getExchangeRate($fromCurrency);
        $toRate = $this->getExchangeRate($toCurrency);

        if ($fromRate === null || $toRate === null) {
            throw new \InvalidArgumentException("Invalid currency code: {$fromCurrency} or {$toCurrency}");
        }

        $amountInUsd = $amount / $fromRate;
        $convertedAmount = $amountInUsd * $toRate;

        return round($convertedAmount, 2);
    }

    public function getExchangeRate(string $currencyCode): ?float
    {
        return Cache::remember(
            "exchange_rate_{$currencyCode}",
            self::CACHE_TTL,
            function () use ($currencyCode) {
                $currency = Currency::where('code', $currencyCode)->first();
                return $currency?->exchange_rate_to_usd;
            }
        );
    }

    public function getSupportedCurrencies(): array
    {
        return Cache::remember('supported_currencies', self::CACHE_TTL, function () {
            return Currency::orderBy('code')
                ->get(['code', 'name', 'symbol'])
                ->toArray();
        });
    }

    public function formatAmount(float $amount, string $currencyCode): string
    {
        $currency = Currency::where('code', $currencyCode)->first();
        $symbol = $currency?->symbol ?? $currencyCode;

        return $symbol . ' ' . number_format($amount, 2);
    }

    public function convertMultiple(array $amounts, string $toCurrency): array
    {
        $converted = [];

        foreach ($amounts as $item) {
            $converted[] = [
                'original_amount' => $item['amount'],
                'original_currency' => $item['currency'],
                'converted_amount' => $this->convert($item['amount'], $item['currency'], $toCurrency),
                'converted_currency' => $toCurrency,
            ];
        }

        return $converted;
    }

    public function getTotalInCurrency(array $amounts, string $toCurrency): float
    {
        $total = 0;

        foreach ($amounts as $item) {
            $total += $this->convert($item['amount'], $item['currency'], $toCurrency);
        }

        return round($total, 2);
    }
}
