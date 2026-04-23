<?php

declare(strict_types=1);

namespace Lmc\Api\Problem\Exception;

use Override;
use Traversable;

final class DomainException extends \DomainException implements ExceptionInterface, ProblemExceptionInterface
{
    protected ?string $type = null;

    protected array $details = [];

    protected ?string $title = null;

    public function setAdditionalDetails(array $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function setType(string $uri): self
    {
        $this->type = $uri;
        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    #[Override]
    public function getAdditionalDetails(): Traversable|array|null
    {
        return $this->details;
    }

    #[Override]
    public function getType(): ?string
    {
        return $this->type;
    }

    #[Override]
    public function getTitle(): ?string
    {
        return $this->title;
    }
}
