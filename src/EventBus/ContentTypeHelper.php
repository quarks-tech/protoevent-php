<?php

namespace Quarks\EventBus;
use Quarks\EventBus\Exception\UnsupportedContentType;

class ContentTypeHelper
{
    public const BASE_CONTENT_TYPE        = 'application';
    public const CLOUDEVENTS_CONTENT_TYPE = 'application/cloudevents';
    public const CLOUDEVENTS_CONTENT_TYPE_JSON = 'application/cloudevents+json';

    public static function extractSubType(string $contentType): string
    {
        if (!str_starts_with($contentType, self::CLOUDEVENTS_CONTENT_TYPE)) {
            if (str_starts_with($contentType, self::BASE_CONTENT_TYPE)) {
                return substr($contentType, strlen(self::BASE_CONTENT_TYPE) + 1);
            }

            throw new UnsupportedContentType();
        }

        $delimiter = $contentType[strlen(self::CLOUDEVENTS_CONTENT_TYPE)];
        if ($delimiter == '+' || $delimiter == ';') {
            return substr($contentType, strlen(self::CLOUDEVENTS_CONTENT_TYPE) + 1);
        }

        throw new UnsupportedContentType();
    }
}
