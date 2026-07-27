<?php

declare(strict_types=1);

namespace Mammatus\Tests\Terraform;

use Mammatus\Terraform\Events\Variables;
use Mammatus\Terraform\Events\Variables\Registry\Entry;
use Mammatus\Terraform\Tfvars;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\Broadcast\ArrayListenerProvider;
use WyriHaximus\Broadcast\Dispatcher;

final class TfvarsTest extends AsyncTestCase
{
    /** @param array<class-string, array<callable>> $listeners */
    #[Test]
    #[DataProvider('exportProvider')]
    public function export(string $expectedOutput, array $listeners): void
    {
        self::expectOutputString($expectedOutput);

        $dispatcher = Dispatcher::createFromListenerProvider(new ArrayListenerProvider($listeners));

        $exitCode = new Tfvars($dispatcher)->export();

        self::assertSame(0, $exitCode->value);
    }

    /** @return iterable<array<string|array<class-string, array<callable>>>> */
    public static function exportProvider(): iterable
    {
        yield 'nothing' => [
            '',
            [],
        ];

        yield 'one' => [
            'app_name = "mammatus-demo"',
            [
                Variables::class => [
                    static function (Variables $variables): void {
                        $variables->add(new Entry('app_name', 'mammatus-demo'));
                    },
                ],
            ],
        ];

        yield 'two' => [
            "rabbitmq_vhost = \"\$HOME_RABBITMQ_VHOST\"\nreplicas       = 3",
            [
                Variables::class => [
                    static function (Variables $variables): void {
                        $variables->add(new Entry('rabbitmq_vhost', '$HOME_RABBITMQ_VHOST'));
                    },
                    static function (Variables $variables): void {
                        $variables->add(new Entry('replicas', 3));
                    },
                ],
            ],
        ];
    }
}
