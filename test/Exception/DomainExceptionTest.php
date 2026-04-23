<?php

declare(strict_types=1);

namespace LmcTest\Api\Problem\Exception;

use Lmc\Api\Problem\Exception;
use PHPUnit\Framework\TestCase;

final class DomainExceptionTest extends TestCase
{
    public function testDomainException(): void
    {
        $exception = new Exception\DomainException('exception message', 401);
        $this->assertEquals([], $exception->getAdditionalDetails());
        $this->assertEquals(null, $exception->getType());
        $this->assertEquals(null, $exception->getTitle());
        $exception->setTitle('problem title')
            ->setType('exception type')
            ->setAdditionalDetails(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $exception->getAdditionalDetails());
        $this->assertEquals('exception type', $exception->getType());
        $this->assertEquals('problem title', $exception->getTitle());
    }
}
