<?php

use App\Http\Controllers\Api\V1\DeliveryController;

function encodeCursor(int $id): string
{
    $controller = new DeliveryController();
    $method = new ReflectionMethod($controller, 'encodeCursor');
    $method->setAccessible(true);
    return $method->invoke($controller, $id);
}

function decodeCursor(string $cursor): ?int
{
    $controller = new DeliveryController();
    $method = new ReflectionMethod($controller, 'decodeCursor');
    $method->setAccessible(true);
    return $method->invoke($controller, $cursor);
}

it('encodes and decodes cursor correctly', function () {
    $cursor = encodeCursor(42);

    expect($cursor)->toBeString()
        ->and(decodeCursor($cursor))->toBe(42);
});

it('returns null for a tampered cursor payload', function () {
    $cursor  = encodeCursor(100);
    $parts   = explode('.', $cursor);
    $tampered = base64_encode('999') . '.' . $parts[1]; // Changed payload, same sig

    expect(decodeCursor($tampered))->toBeNull();
});

it('returns null for a tampered cursor signature', function () {
    $cursor = encodeCursor(100);
    $parts  = explode('.', $cursor);
    $tampered = $parts[0] . '.0000000000000000'; // Valid payload, wrong sig

    expect(decodeCursor($tampered))->toBeNull();
});

it('returns null for malformed cursor with no dot separator', function () {
    expect(decodeCursor('nodothere'))->toBeNull();
});

it('different IDs produce different cursors', function () {
    expect(encodeCursor(1))->not->toBe(encodeCursor(2));
});
