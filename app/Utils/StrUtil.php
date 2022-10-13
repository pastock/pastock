<?php

namespace App\Utils;

class StrUtil
{
    /**
     * 把多個空白取代成一個
     */
    public static function replaceMultiSpaceToOne(string $source): string
    {
        return preg_replace('/\s+/', ' ', $source);
    }
}
