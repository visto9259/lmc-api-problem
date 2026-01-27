<?php

declare(strict_types=1);

namespace LmcTest\Api\Problem;

use Lmc\Api\Problem\ApiProblem;
use PHPUnit\Framework\TestCase;

final class ApiProblemTest extends TestCase
{
    public function testApiProblem(): void
    {
        $problem = new ApiProblem(400, 'Bad Request');
        $this->assertEquals(400, $problem->getStatus());
    }
}
