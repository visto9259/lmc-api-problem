<?php

declare(strict_types=1);

namespace Lmc\Api\Problem;

use JsonException;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\InjectContentTypeTrait;
use Laminas\Diactoros\Stream;
use Lmc\Api\Problem\Exception\InvalidArgumentException;

use function is_resource;
use function json_encode;
use function sprintf;

use const JSON_HEX_AMP;
use const JSON_HEX_APOS;
use const JSON_HEX_QUOT;
use const JSON_HEX_TAG;
use const JSON_PARTIAL_OUTPUT_ON_ERROR;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

final class ApiProblemResponse extends Response
{
    use InjectContentTypeTrait;

    /**
     * Default flags for json_encode
     */
    public const int DEFAULT_JSON_FLAGS = JSON_HEX_TAG
    | JSON_HEX_APOS
    | JSON_HEX_AMP
    | JSON_HEX_QUOT
    | JSON_UNESCAPED_SLASHES
    | JSON_PARTIAL_OUTPUT_ON_ERROR;

    public function __construct(ApiProblem $apiProblem)
    {
        $json = $this->jsonEncode($apiProblem->toArray(), self::DEFAULT_JSON_FLAGS);

        $body = $this->createBodyFromJson($json);

        $headers = $this->injectContentType('application/problem+json', []);

        parent::__construct($body, $apiProblem->getStatus(), $headers);
    }

    private function createBodyFromJson(false|string $json): Stream
    {
        $body = new Stream('php://temp', 'wb+');
        $body->write($json);
        $body->rewind();
        return $body;
    }

    private function jsonEncode(mixed $data, int $encodingOptions): string
    {
        if (is_resource($data)) {
            throw new InvalidArgumentException('Cannot JSON encode resource');
        }

        try {
            return json_encode($data, $encodingOptions | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException(sprintf(
                'Unable to encode data to JSON in %s: %s',
                self::class,
                $e->getMessage()
            ), 0, $e);
        }
    }
}
