<?php

use App\Support\Money;

/*
| M1: centavo helpers (D-001).
*/

it('converts pesos to centavos without float drift', function (int|float|string $pesos, int $cents) {
    expect(Money::fromPesos($pesos))->toBe($cents);
})->with([
    [7000, 700000],
    [0.1 + 0.2, 30],
    ['1234.56', 123456],
    [19.99, 1999],
]);

it('formats centavos as pesos', function (int $cents, string $formatted) {
    expect(Money::format($cents))->toBe($formatted);
})->with([
    [700000, '₱7,000.00'],
    [1500000, '₱15,000.00'],
    [5, '₱0.05'],
    [-100000, '-₱1,000.00'],
]);

it('writes plain decimals for CSV exports', function (int $cents, string $decimal) {
    expect(Money::decimal($cents))->toBe($decimal);
})->with([
    [700050, '7000.50'],
    [5, '0.05'],
    [0, '0.00'],
    [-123456, '-1234.56'],
]);

it('formats compact chart labels', function (int $cents, string $label) {
    expect(Money::compact($cents))->toBe($label);
})->with([
    [0, '₱0'],
    [50000, '₱500'],
    [1250000, '₱12.5k'],
    [1000000, '₱10k'],
    [250000000, '₱2.5M'],
]);
