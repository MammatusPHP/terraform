<?php

declare(strict_types=1);

namespace Mammatus\Tests\Terraform\Encoder;

use Mammatus\Terraform\Encoder\InvalidTfvarsValue;
use Mammatus\Terraform\Encoder\Tfvars;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use WyriHaximus\TestUtilities\TestCase;

final class TfvarsTest extends TestCase
{
    /** @return iterable<string, array{0: mixed, 1: string}> */
    public static function provideValues(): iterable
    {
        yield 'string' => ['mammatus-demo', '"mammatus-demo"'];
        yield 'string-with-quotes' => ['foo"bar', '"foo\"bar"'];
        yield 'env-reference' => ['$HOME_RABBITMQ_VHOST', '"$HOME_RABBITMQ_VHOST"'];
        yield 'integer' => [3, '3'];
        yield 'float' => [3.5, '3.5'];
        yield 'true' => [true, 'true'];
        yield 'false' => [false, 'false'];
        yield 'null' => [null, 'null'];
        yield 'empty-list' => [[], '[]'];
        yield 'list' => [['a', 'b'], '["a", "b"]'];
        yield 'map' => [['key' => 'value'], '{ key = "value" }'];
    }

    #[DataProvider('provideValues')]
    #[Test]
    public function encodeValue(mixed $value, string $expected): void
    {
        self::assertSame($expected, new Tfvars()->encodeValue($value));
    }

    #[Test]
    public function encodeEntries(): void
    {
        $encoded = new Tfvars()->encode([
            'rabbitmq_vhost' => [
                'name' => 'rabbitmq_vhost',
                'value' => '$HOME_RABBITMQ_VHOST',
            ],
            'replicas' => [
                'name' => 'replicas',
                'value' => 3,
            ],
        ]);

        self::assertSame(
            "rabbitmq_vhost = \"\$HOME_RABBITMQ_VHOST\"\nreplicas       = 3",
            $encoded,
        );
    }

    #[Test]
    public function encodeEmptyEntries(): void
    {
        self::assertSame('', new Tfvars()->encode([]));
    }

    #[Test]
    public function encodeValueRejectsObjects(): void
    {
        try {
            new Tfvars()->encodeValue(new stdClass());
            self::fail('Expected InvalidTfvarsValue to be thrown');
        } catch (InvalidTfvarsValue $exception) {
            self::assertSame('Value of type stdClass cannot be encoded as Terraform tfvars', $exception->getMessage());
        }
    }
}
