<?php

declare(strict_types=1);

/*
 * This file is part of the Dowsing Machine project.
 *
 * (c) Dowsing Machine contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

/**
 * Swaps the TCGdex CDN host for our own card-image host (CARD_IMAGE_DOMAIN).
 *
 * The database keeps canonical assets.tcgdex.net URLs; entities expose the public
 * host on read ({@see toPublic()}) and store the canonical one on write
 * ({@see toCanonical()}), so unsetting the env var reverts every URL at once.
 * Static because entities have no DI: configured once in Kernel::boot().
 *
 * @see docs/features.md F6.2 — TCGdex card data enrichment
 * @see docs/technicalities/enrichment.md
 */
final class CardImageHost
{
    public const string TCGDEX_BASE = 'https://assets.tcgdex.net';

    private static ?string $publicBase = null;

    public static function configure(?string $domain): void
    {
        self::$publicBase = null === $domain || '' === $domain ? null : 'https://'.$domain;
    }

    /**
     * @return ($url is null ? null : string)
     */
    public static function toPublic(?string $url): ?string
    {
        return null === self::$publicBase || null === $url ? $url : self::swap($url, self::TCGDEX_BASE, self::$publicBase);
    }

    /**
     * @return ($url is null ? null : string)
     */
    public static function toCanonical(?string $url): ?string
    {
        return null === self::$publicBase || null === $url ? $url : self::swap($url, self::$publicBase, self::TCGDEX_BASE);
    }

    private static function swap(string $url, string $from, string $to): string
    {
        return str_starts_with($url, $from.'/') ? $to.substr($url, \strlen($from)) : $url;
    }
}
