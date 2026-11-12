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

namespace App\Tests\Service;

use App\Entity\CardPrinting;
use App\Service\CardImageHost;
use App\Service\DeckList\MinifiedCardView;
use PHPUnit\Framework\TestCase;

final class CardImageHostTest extends TestCase
{
    private const string TCGDEX_URL = 'https://assets.tcgdex.net/en/me/me06/122/high.webp';
    private const string PUBLIC_URL = 'https://card.dowsingmachine.com/en/me/me06/122/high.webp';

    protected function tearDown(): void
    {
        CardImageHost::configure(null);
    }

    public function testUnconfiguredLeavesUrlsUntouched(): void
    {
        CardImageHost::configure('');

        self::assertSame(self::TCGDEX_URL, CardImageHost::toPublic(self::TCGDEX_URL));
        self::assertNull(CardImageHost::toPublic(null));
    }

    public function testSwapsOnlyTheTcgdexHost(): void
    {
        CardImageHost::configure('card.dowsingmachine.com');

        self::assertSame(self::PUBLIC_URL, CardImageHost::toPublic(self::TCGDEX_URL));
        self::assertSame(self::PUBLIC_URL, CardImageHost::toPublic(self::PUBLIC_URL));
        self::assertSame(self::TCGDEX_URL, CardImageHost::toCanonical(self::PUBLIC_URL));
        self::assertSame('https://images.pokemontcg.io/sm35/7_hires.png', CardImageHost::toPublic('https://images.pokemontcg.io/sm35/7_hires.png'));
        self::assertSame('https://assets.tcgdex.net.evil.example/x', CardImageHost::toPublic('https://assets.tcgdex.net.evil.example/x'));
    }

    public function testCardPrintingStoresCanonicalAndExposesPublic(): void
    {
        CardImageHost::configure('card.dowsingmachine.com');

        $printing = new CardPrinting();
        $printing->setImageUrl(self::PUBLIC_URL);

        self::assertSame(self::PUBLIC_URL, $printing->getImageUrl());
        self::assertSame(self::TCGDEX_URL, (new \ReflectionProperty(CardPrinting::class, 'imageUrl'))->getValue($printing));
    }

    public function testMinifiedCacheStoresCanonical(): void
    {
        CardImageHost::configure('card.dowsingmachine.com');

        $view = new MinifiedCardView('Mega Golurk ex', 1, 'DLR', '122', 'pokemon', null, self::PUBLIC_URL);
        $json = MinifiedCardView::serializeGrouped(['pokemon' => [$view]]);

        self::assertStringContainsString(self::TCGDEX_URL, $json);
        self::assertSame(self::PUBLIC_URL, MinifiedCardView::deserializeGrouped($json)['pokemon'][0]->getImageUrl());
    }
}
