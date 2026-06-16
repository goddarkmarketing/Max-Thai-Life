<?php
declare(strict_types=1);

final class JsReader
{
    public static function readFile(string $filename): string
    {
        $path = JS_PATH . '/' . $filename;
        if (!is_file($path)) {
            throw new RuntimeException("ไม่พบ {$path}");
        }
        return file_get_contents($path) ?: '';
    }

    public static function contains(string $filename, string $needle): bool
    {
        return str_contains(self::readFile($filename), $needle);
    }

    /** @return array<string, mixed> */
    public static function decodeWindowVar(string $filename, string $varName): array
    {
        $content = self::readFile($filename);
        $prefix = 'window.' . $varName . ' = ';
        $pos = strpos($content, $prefix);
        if ($pos === false) {
            throw new RuntimeException("ไม่พบ window.{$varName} ใน {$filename}");
        }

        $json = self::extractBalancedJson(substr($content, $pos + strlen($prefix)));
        $json = self::jsLiteralToJson($json);
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new RuntimeException("แปลง {$varName} จาก JS ไม่สำเร็จ");
        }
        return $data;
    }

    private static function jsLiteralToJson(string $js): string
    {
        $prev = '';
        $out = $js;
        while ($out !== $prev) {
            $prev = $out;
            $replaced = preg_replace(
                '/(^|[{\[,])\s*([a-zA-Z_][a-zA-Z0-9_]*)(\s*:)/m',
                '$1"$2"$3',
                $out
            );
            $out = $replaced ?? $out;
        }
        return $out;
    }

    private static function extractBalancedJson(string $text): string
    {
        $text = ltrim($text);
        $open = $text[0] ?? '';
        if ($open !== '{' && $open !== '[') {
            throw new RuntimeException('รูปแบบ JS ไม่รองรับ');
        }
        $close = $open === '{' ? '}' : ']';
        $depth = 0;
        $inString = false;
        $escape = false;
        $len = strlen($text);

        for ($i = 0; $i < $len; $i++) {
            $ch = $text[$i];
            if ($inString) {
                if ($escape) {
                    $escape = false;
                    continue;
                }
                if ($ch === '\\') {
                    $escape = true;
                    continue;
                }
                if ($ch === '"') {
                    $inString = false;
                }
                continue;
            }
            if ($ch === '"') {
                $inString = true;
                continue;
            }
            if ($ch === $open) {
                $depth++;
            } elseif ($ch === $close) {
                $depth--;
                if ($depth === 0) {
                    return substr($text, 0, $i + 1);
                }
            }
        }

        throw new RuntimeException('parse JSON จาก JS ไม่สมบูรณ์');
    }
}
