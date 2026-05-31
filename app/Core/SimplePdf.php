<?php

namespace App\Core;

class SimplePdf
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const LEFT = 42;
    private const TOP = 800;
    private const LINE_HEIGHT = 14;

    public static function fromLines(string $title, array $lines): string
    {
        $pages = [];
        $current = [];
        $cursor = self::TOP;

        $addLine = function (string $line, int $size = 10) use (&$pages, &$current, &$cursor): void {
            if ($cursor < 46) {
                $pages[] = $current;
                $current = [];
                $cursor = self::TOP;
            }

            $current[] = ['text' => $line, 'size' => $size, 'y' => $cursor];
            $cursor -= $size >= 14 ? 20 : self::LINE_HEIGHT;
        };

        $addLine($title, 16);
        $addLine('Gerado em ' . date('d/m/Y H:i'), 9);
        $addLine('', 10);

        foreach ($lines as $line) {
            foreach (self::wrap((string) $line, 92) as $wrapped) {
                $addLine($wrapped, 9);
            }
        }

        if ($current) {
            $pages[] = $current;
        }

        return self::build($pages ?: [[]]);
    }

    private static function wrap(string $text, int $width): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        if ($text === '') {
            return [''];
        }

        return explode("\n", wordwrap($text, $width, "\n", true));
    }

    private static function build(array $pages): string
    {
        $objects = [];
        $pageIds = [];

        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        foreach ($pages as $page) {
            $content = "BT\n";
            foreach ($page as $line) {
                $content .= '/F1 ' . (int) $line['size'] . " Tf\n";
                $content .= self::LEFT . ' ' . (int) $line['y'] . " Td\n";
                $content .= '(' . self::escape($line['text']) . ") Tj\n";
                $content .= '-' . self::LEFT . " 0 Td\n";
            }
            $content .= "ET\n";

            $streamId = count($objects) + 1;
            $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream";

            $pageId = count($objects) + 1;
            $pageIds[] = $pageId;
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $streamId . ' 0 R >>';
        }

        $objects[1] = '<< /Type /Pages /Kids [' . implode(' ', array_map(fn (int $id): string => $id . ' 0 R', $pageIds)) . '] /Count ' . count($pageIds) . ' >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[$index + 1] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1, $total = count($objects); $i <= $total; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        return $pdf;
    }

    private static function escape(string $text): string
    {
        $encoded = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
        $encoded = $encoded !== false ? $encoded : $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
    }
}
