<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\Encoding;

use QuarksTech\ProtoEvent\Exception\EncodingException;

/**
 * Registry for message codecs, indexed by content type
 */
final class CodecRegistry
{
    /** @var array<string, CodecInterface> */
    private array $codecs = [];

    private static function normalizeContentType(string $contentType): string
    {
        $contentType = strtolower(trim($contentType));
        // Strip parameters like "; charset=utf-8"
        $semi = strpos($contentType, ';');
        if ($semi !== false) {
            $contentType = trim(substr($contentType, 0, $semi));
        }
        return $contentType;
    }

    public function __construct()
    {
        // Register default codecs
        $this->register(new ProtobufCodec());
        $this->register(new JsonCodec());
    }

    public function register(CodecInterface $codec): void
    {
        $contentType = self::normalizeContentType($codec->getContentType());
        $this->codecs[$contentType] = $codec;

        // Also register cloudevents variants
        if (str_contains($contentType, '/')) {
            $subtype = substr($contentType, (int) strpos($contentType, '/') + 1);
            $this->codecs["application/cloudevents+{$subtype}"] = $codec;
        }
    }

    public function getCodec(string $contentType): CodecInterface
    {
        $contentType = self::normalizeContentType($contentType);
        if (isset($this->codecs[$contentType])) {
            return $this->codecs[$contentType];
        }

        // Try extracting subtype from cloudevents content type
        if (str_contains($contentType, '+')) {
            $subtype = substr($contentType, (int) strpos($contentType, '+') + 1);
            $baseType = "application/{$subtype}";
            if (isset($this->codecs[$baseType])) {
                return $this->codecs[$baseType];
            }
        }

        throw new EncodingException("No codec registered for content type: {$contentType}");
    }

    public function hasCodec(string $contentType): bool
    {
        try {
            $this->getCodec($contentType);
            return true;
        } catch (EncodingException) {
            return false;
        }
    }
}
