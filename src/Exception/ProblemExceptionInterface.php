<?php

declare(strict_types=1);

namespace Lmc\Api\Problem\Exception;

use Traversable;

/**
 * Interface for exceptions that can provide additional API Problem details.
 */
interface ProblemExceptionInterface
{
    public function getAdditionalDetails(): Traversable|array|null;

    public function getType(): string;

    public function getTitle(): string;
}
