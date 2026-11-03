<?php

use App\Support\PhoneNumber;

it('normalises the ways Kenyans write mobile numbers', function (string $input, ?string $expected) {
    expect(PhoneNumber::normalise($input))->toBe($expected);
})->with([
    ['0712345678', '+254712345678'],
    ['0712 345 678', '+254712345678'],
    ['712345678', '+254712345678'],
    ['+254712345678', '+254712345678'],
    ['254712345678', '+254712345678'],
    ['0110 123 456', '+254110123456'],
    ['0812345678', null],
    ['12345', null],
    ['+255712345678', null],
    ['not a number', null],
]);

it('masks all but the last three digits', function () {
    expect(PhoneNumber::mask('+254712345678'))->toBe('+254 *** *** 678');
});
