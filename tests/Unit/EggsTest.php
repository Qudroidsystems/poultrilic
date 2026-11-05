<?php

use App\Support\Eggs;

it('parses legacy egg strings into pieces', function (mixed $input, int $expected) {
    expect(Eggs::parse($input))->toBe($expected);
})->with([
    'crates and pieces' => ['11 Cr 12PC', 342],
    'zero' => ['0 Cr 0PC', 0],
    'crates only upper' => ['12 CR', 360],
    'thousands separator' => ['1,234 Cr', 37020],
    'big total from the dashboard comment' => ['5741 Cr 2PC', 172232],
    'thousands separator with pieces' => ['1,000 Cr 5PC', 30005],
    'spaced pieces' => ['3 Cr 4 PC', 94],
    'bare number is pieces' => ['45', 45],
    'integer' => [45, 45],
    'empty string' => ['', 0],
    'null' => [null, 0],
    'garbage' => ['n/a', 0],
]);

it('formats pieces as crates and pieces', function () {
    expect(Eggs::format(342))->toBe('11 Cr 12PC')
        ->and(Eggs::format(0))->toBe('0 Cr 0PC')
        ->and(Eggs::format(-5))->toBe('0 Cr 0PC')
        ->and(Eggs::format(null))->toBe('0 Cr 0PC')
        ->and(Eggs::crates(342))->toBe(11)
        ->and(Eggs::remainder(342))->toBe(12)
        ->and(Eggs::toPieces(11, 12))->toBe(342);
});

it('round-trips parse(format(n))', function () {
    foreach ([0, 1, 29, 30, 31, 342, 172232] as $n) {
        expect(Eggs::parse(Eggs::format($n)))->toBe($n);
    }
});
