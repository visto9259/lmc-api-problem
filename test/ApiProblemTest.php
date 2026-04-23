<?php

declare(strict_types=1);

namespace LmcTest\Api\Problem;

use Lmc\Api\Problem\ApiProblem;
use Lmc\Api\Problem\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

#[CoversClass(ApiProblem::class)]
final class ApiProblemTest extends TestCase
{
    /** @psalm-return array<array-key, list{int}> */
    public static function statusCodesProvider(): array
    {
        return [
            '200' => [200],
            '201' => [201],
            '300' => [300],
            '301' => [301],
            '302' => [302],
            '400' => [400],
            '401' => [401],
            '404' => [404],
            '500' => [500],
        ];
    }

    #[DataProvider('statusCodesProvider')]
    public function testStatusIsUsedVerbatim(int $status): void
    {
        $apiProblem = new ApiProblem($status, 'foo');
        $payload    = $apiProblem->toArray();
        $this->assertArrayHasKey('status', $payload);
        $this->assertEquals($status, $payload['status']);
    }

    public function testApiProblem(): void
    {
        $problem = new ApiProblem(400, 'Bad Request');
        $this->assertEquals(400, $problem->getStatus());
    }

    public function testInvalidHttpStatusCode(): void
    {
        $problem = new ApiProblem(99, 'Bad Request');
        $this->assertEquals(500, $problem->getStatus());
        $problem = new ApiProblem(600, 'Bad Request');
        $this->assertEquals(500, $problem->getStatus());
    }

    public function testGet(): void
    {
        $problem = new ApiProblem(
            400,
            'Bad Request',
            'type ref',
            'foo',
            [
                'foo' => 'bar',
            ],
        );
        /** @psalm-suppress InaccessibleProperty */
        $this->assertEquals('foo', $problem->title);
        /** @psalm-suppress InaccessibleProperty */
        $this->assertEquals(400, $problem->status);
        /** @psalm-suppress InaccessibleProperty */
        $this->assertEquals('Bad Request', $problem->detail);
        /** @psalm-suppress InaccessibleProperty */
        $this->assertEquals('type ref', $problem->type);
        /** @psalm-suppress UndefinedMagicPropertyFetch */
        $this->assertEquals('bar', $problem->foo);
        /** @psalm-suppress UndefinedMagicPropertyFetch */
        $this->assertEquals('bar', $problem->FOO);
        // test invalid
        $this->expectException(Exception\InvalidArgumentException::class);
        /**
         * @psalm-suppress UndefinedMagicPropertyFetch
         * @psalm-suppress MixedAssignment
         */
        $a = $problem->badProperty;
    }

    public function testExceptionCodeIsUsedForStatus(): void
    {
        $exception  = new \Exception('exception message', 401);
        $apiProblem = new ApiProblem('500', $exception);
        $payload    = $apiProblem->toArray();
        $this->assertArrayHasKey('status', $payload);
        $this->assertEquals($exception->getCode(), $payload['status']);
    }

    public function testDetailStringIsUsedVerbatim(): void
    {
        $apiProblem = new ApiProblem('500', 'foo');
        $payload    = $apiProblem->toArray();
        $this->assertArrayHasKey('detail', $payload);
        $this->assertEquals('foo', $payload['detail']);
    }

    public function testExceptionMessageIsUsedForDetail(): void
    {
        $exception  = new \Exception('exception message');
        $apiProblem = new ApiProblem('500', $exception);
        $payload    = $apiProblem->toArray();
        $this->assertArrayHasKey('detail', $payload);
        $this->assertEquals($exception->getMessage(), $payload['detail']);
    }

    public function testExceptionsCanTriggerInclusionOfStackTraceInDetails(): void
    {
        $exception  = new \Exception('exception message');
        $apiProblem = new ApiProblem('500', $exception);
        $apiProblem->setDetailIncludesStackTrace(true);
        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('trace', $payload);
        $this->assertIsArray($payload['trace']);
        $this->assertEquals($exception->getTrace(), $payload['trace']);
    }

    public function testExceptionsCanTriggerInclusionOfNestedExceptions(): void
    {
        $exceptionChild  = new \Exception('child exception');
        $exceptionParent = new \Exception('parent exception', 0, $exceptionChild);

        $apiProblem = new ApiProblem('500', $exceptionParent);
        $apiProblem->setDetailIncludesStackTrace(true);
        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('exception_stack', $payload);
        $this->assertIsArray($payload['exception_stack']);
        $expected = [
            [
                'code'    => $exceptionChild->getCode(),
                'message' => $exceptionChild->getMessage(),
                'trace'   => $exceptionChild->getTrace(),
            ],
        ];
        $this->assertEquals($expected, $payload['exception_stack']);
    }

    public function testTypeUrlIsUsedVerbatim(): void
    {
        $apiProblem = new ApiProblem('500', 'foo', 'http://status.dev:8080/details.md');
        $payload    = $apiProblem->toArray();
        $this->assertArrayHasKey('type', $payload);
        $this->assertEquals('http://status.dev:8080/details.md', $payload['type']);
    }

    /** @psalm-return array<array-key, list{int}> */
    public static function knownStatusCodesProvider(): array
    {
        return [
            '404' => [404],
            '409' => [409],
            '422' => [422],
            '500' => [500],
        ];
    }

    #[DataProvider('knownStatusCodesProvider')]
    public function testKnownStatusResultsInKnownTitle(int $status): void
    {
        $apiProblem = new ApiProblem($status, 'foo');
        $r          = new ReflectionObject($apiProblem);
        $p          = $r->getProperty('problemStatusTitles');
//        $p->setAccessible(true);
        /** @var string[] $titles */
        $titles = $p->getValue($apiProblem);

        $payload = $apiProblem->toArray();
        $this->assertArrayHasKey('title', $payload);
        $this->assertEquals($titles[$status], $payload['title']);
    }

    public function testUnknownStatusResultsInUnknownTitle(): void
    {
        $apiProblem = new ApiProblem(420, 'foo');
        $payload    = $apiProblem->toArray();
        $this->assertArrayHasKey('title', $payload);
        $this->assertEquals('Unknown', $payload['title']);
    }

    public function testProvidedTitleIsUsedVerbatim(): void
    {
        $apiProblem = new ApiProblem('500', 'foo', 'http://status.dev:8080/details.md', 'some title');
        $payload    = $apiProblem->toArray();
        $this->assertArrayHasKey('title', $payload);
        $this->assertEquals('some title', $payload['title']);
    }

    public function testCanPassArbitraryDetailsToConstructor(): void
    {
        $problem = new ApiProblem(
            400,
            'Invalid input',
            'http://example.com/api/problem/400',
            'Invalid entity',
            ['foo' => 'bar']
        );
        /** @psalm-suppress UndefinedMagicPropertyFetch */
        $this->assertEquals('bar', $problem->foo);
    }

    public function testArraySerializationIncludesArbitraryDetails(): void
    {
        $problem = new ApiProblem(
            400,
            'Invalid input',
            'http://example.com/api/problem/400',
            'Invalid entity',
            ['foo' => 'bar']
        );
        $array   = $problem->toArray();
        $this->assertArrayHasKey('foo', $array);
        $this->assertEquals('bar', $array['foo']);
    }

    public function testArbitraryDetailsShouldNotOverwriteRequiredFieldsInArraySerialization(): void
    {
        $problem = new ApiProblem(
            400,
            'Invalid input',
            'http://example.com/api/problem/400',
            'Invalid entity',
            ['title' => 'SHOULD NOT GET THIS']
        );
        $array   = $problem->toArray();
        $this->assertArrayHasKey('title', $array);
        $this->assertEquals('Invalid entity', $array['title']);
    }

    public function testUsesTitleFromExceptionWhenProvided(): void
    {
        $exception = new Exception\DomainException('exception message', 401);
        $exception->setTitle('problem title');
        $apiProblem = new ApiProblem('401', $exception);
        $payload    = $apiProblem->toArray();
        $this->assertArrayHasKey('title', $payload);
        $this->assertEquals($exception->getTitle(), $payload['title']);
    }

    public function testUsesTypeFromExceptionWhenProvided(): void
    {
        $exception = new Exception\DomainException('exception message', 401);
        $exception->setType('http://example.com/api/help/401');
        $apiProblem = new ApiProblem('401', $exception);
        $payload    = $apiProblem->toArray();
        $this->assertArrayHasKey('type', $payload);
        $this->assertEquals($exception->getType(), $payload['type']);
    }

    public function testUsesAdditionalDetailsFromExceptionWhenProvided(): void
    {
        $exception = new Exception\DomainException('exception message', 401);
        $exception->setAdditionalDetails(['foo' => 'bar']);
        $apiProblem = new ApiProblem('401', $exception);
        $payload    = $apiProblem->toArray();
        $this->assertArrayHasKey('foo', $payload);
        $this->assertEquals('bar', $payload['foo']);
    }

    /** @psalm-return array<array-key, list{int}> */
    public static function invalidStatusCodesProvider(): array
    {
        return [
            '-1'  => [-1],
            '0'   => [0],
            '7'   => [7], // reported
            '14'  => [14], // observed
            '600' => [600],
        ];
    }

    #[DataProvider('invalidStatusCodesProvider')]
    public function testInvalidHttpStatusCodesAreCastTo500(int $code): void
    {
        $e       = new \Exception('Testing', $code);
        $problem = new ApiProblem($code, $e);
        /** @psalm-suppress InaccessibleProperty */
        $this->assertEquals(500, $problem->status);
    }
}
