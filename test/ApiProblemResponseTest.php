<?php

declare(strict_types=1);

namespace LmcTest\Api\Problem;

use Lmc\Api\Problem\ApiProblem;
use Lmc\Api\Problem\ApiProblemResponse;
use Lmc\Api\Problem\Exception;
use PHPUnit\Framework\TestCase;

use function fopen;
use function json_decode;
use function strtolower;

final class ApiProblemResponseTest extends TestCase
{
    public function testApiProblemResponseSetsStatusCodeAndReasonPhrase(): void
    {
        $response = new ApiProblemResponse(new ApiProblem(400, 'Random error'));
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertIsString($response->getReasonPhrase());
        $this->assertNotEmpty($response->getReasonPhrase());
        $this->assertEquals('bad request', strtolower($response->getReasonPhrase()));
    }

    public function testApiProblemResponseSetsStatusCodeAndReasonPhraseUsingException(): void
    {
        $exception = new Exception\DomainException('Random error', 400);
        $response  = new ApiProblemResponse(new ApiProblem(400, $exception));
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertIsString($response->getReasonPhrase());
        $this->assertNotEmpty($response->getReasonPhrase());
        $this->assertEquals('bad request', strtolower($response->getReasonPhrase()));
    }

    public function testApiProblemResponseBodyIsSerializedApiProblem(): void
    {
        $additional = [
            'foo' => fopen('php://memory', 'r'),
        ];

        $expected = [
            'foo'    => null,
            'type'   => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
            'title'  => 'Bad Request',
            'status' => 400,
            'detail' => 'Random error',
        ];

        $apiProblem = new ApiProblem(400, 'Random error', null, null, $additional);
        $response   = new ApiProblemResponse($apiProblem);
        $this->assertEquals($expected, json_decode($response->getBody()->getContents(), true));
    }

    public function testApiProblemResponseSetsContentTypeHeader(): void
    {
        $response = new ApiProblemResponse(new ApiProblem(400, 'Random error'));
        $headers  = $response->getHeaders();
        $this->assertArrayHasKey('content-type', $headers);
        $header = $headers['content-type'][0];
        $this->assertEquals(ApiProblem::CONTENT_TYPE, $header);
    }
}
