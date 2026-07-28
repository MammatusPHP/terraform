<?php

declare(strict_types=1);

namespace Mammatus\Terraform;

use Mammatus\ExitCode;
use Mammatus\Terraform\Encoder\Tfvars as TfvarsEncoder;
use Mammatus\Terraform\Events\Variables;
use Psr\EventDispatcher\EventDispatcherInterface;

use const PHP_EOL;

/** @api */
final readonly class Tfvars
{
    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function export(): ExitCode
    {
        $variables = Variables::create();
        $this->eventDispatcher->dispatch($variables);

        echo new TfvarsEncoder()->encode($variables->get()), PHP_EOL;

        return ExitCode::Success;
    }
}
