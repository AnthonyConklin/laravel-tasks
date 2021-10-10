<?php

namespace AnthonyConklin\LaravelTasks;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class XmlParser
{
    public function __construct($xml, $normalizeKeys = false)
    {
        $this->xml = $xml;
        $this->normalizeKeys = $normalizeKeys;
    }

    public static function parseString($string, $normalizeKeys = true)
    {
        return self::toArray($string, $normalizeKeys);
    }

    public static function parseRequest(Request $request, $normalizeKeys = true)
    {
        return XmlParser::toArray($request->getContent(), $normalizeKeys);
    }

    public static function parseFile($file, $normalizeKeys = false)
    {
        $xml = file_get_contents($file);

        return self::toArray($xml, $normalizeKeys);
    }

    public static function toArray($string, $normalizeKeys = true)
    {
        $xml = simplexml_load_string($string, "SimpleXMLElement", LIBXML_NOCDATA);
        $requestType = $xml->getName();
        $json = json_encode($xml);
        $json = json_decode($json, true);

        return collect([
            'type' => $requestType,
            'data' => self::makeCollection($json, $normalizeKeys),
        ]);
    }

    public static function makeCollection($array, $normalizeKeys = true)
    {
        $array = array_map(function ($value) use ($normalizeKeys) {
            if (is_array($value) && count($value) >= 1) {
                return self::makeCollection($value, $normalizeKeys);
            }
            if (is_array($value) && count($value) == 0) {
                return null;
            }

            return $value;
        }, $array);

        if ($normalizeKeys) {
            $newArray = [];
            foreach ($array as $key => $value) {
                $newArray[self::generateNormalizedKey($key)] = $value;
            }
            $array = $newArray;
        }

        return collect($array);
    }

    public static function generateNormalizedKey($key)
    {
        $key = Str::snake($key);

        $key = explode('_', $key);

        $newKey = '';
        $previousStrLength = 0;
        foreach ($key as $index => $part) {
            if (strlen($part) > 1 && $index !== 0) {
                $newKey .= '_' . $part;
            } else {
                if ($previousStrLength > 1 && strlen($part) === 1) {
                    $newKey .= '_';
                }
                $newKey .= $part;
            }
            $previousStrLength = strlen($part);
        }

        return $newKey;
    }

    public static function test()
    {
    }
}
