<?php

namespace App\Libraries;

/**
 * Cleans user-controlled text before it is placed in an LLM prompt.
 *
 * This does not make prompt injection impossible. It removes the cheap tricks
 * (control characters, fake delimiter tags, very long payloads) so that item
 * names and descriptions stay short, single-block data. The real protection is
 * that the model has no tools and the endpoint is owner/admin only.
 */
final class PromptText
{
    public static function clean(?string $value, int $maxLength): string
    {
        $value = (string) $value;

        // Drop invalid UTF-8 so mb_* functions and json_encode() cannot choke on it.
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');

        // Control characters (including newlines and tabs) become spaces.
        $value = (string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value);

        // Remove angle brackets so text cannot open or close the <item_data> block.
        $value = str_replace(['<', '>'], '', $value);

        // Collapse runs of whitespace and trim.
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        if ($maxLength > 0 && mb_strlen($value, 'UTF-8') > $maxLength) {
            $value = rtrim(mb_substr($value, 0, $maxLength, 'UTF-8')) . '...';
        }

        return $value;
    }
}
