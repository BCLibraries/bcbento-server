<?php

namespace App\Service;

class ElasticSearchCleaner
{
    public static function clean(string $search_term): string
    {
        $regex = "/[\\+\\-\\=\\&\\|\\!\\(\\)\\{\\}\\[\\]\\^\\\"\\~\\*\\<\\>\\?\\:\\\\\\/]/";
        return preg_replace($regex, addslashes('\\$0'), $search_term);
    }
}