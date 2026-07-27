<?php

declare(strict_types=1);

namespace Mammatus\Terraform\Encoder;

use function array_is_list;
use function get_debug_type;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function max;
use function str_pad;
use function str_replace;
use function strlen;

use const STR_PAD_RIGHT;

final class Tfvars
{
    /** @param array<string, array{name: string, value: mixed}> $entries */
    public function encode(array $entries): string
    {
        if ($entries === []) {
            return '';
        }

        $maxNameLength = 0;

        foreach ($entries as $entry) {
            $maxNameLength = max($maxNameLength, strlen($entry['name']));
        }

        $lines = [];

        foreach ($entries as $entry) {
            $lines[] = str_pad($entry['name'], $maxNameLength, ' ', STR_PAD_RIGHT) . ' = ' . $this->encodeValue($entry['value']);
        }

        return implode("\n", $lines);
    }

    public function encodeValue(mixed $value): string
    {
        if (is_string($value)) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_array($value)) {
            return $this->encodeArray($value);
        }

        throw new InvalidTfvarsValue('Value of type ' . get_debug_type($value) . ' cannot be encoded as Terraform tfvars');
    }

    /** @param array<int|string, mixed> $value */
    private function encodeArray(array $value): string
    {
        if ($value === []) {
            return '[]';
        }

        if (array_is_list($value)) {
            $items = [];

            foreach ($value as $item) {
                $items[] = $this->encodeValue($item);
            }

            return '[' . implode(', ', $items) . ']';
        }

        $items = [];

        foreach ($value as $key => $item) {
            $items[] = $key . ' = ' . $this->encodeValue($item);
        }

        return '{ ' . implode(', ', $items) . ' }';
    }
}
