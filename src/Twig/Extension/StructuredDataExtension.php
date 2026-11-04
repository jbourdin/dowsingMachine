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

namespace App\Twig\Extension;

use App\Twig\Runtime\StructuredDataRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @see docs/features.md F18.27 — JSON-LD structured data
 */
class StructuredDataExtension extends AbstractExtension
{
    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('structured_data', [StructuredDataRuntime::class, 'getBuilder']),
            new TwigFunction('json_ld', [StructuredDataRuntime::class, 'jsonLd'], ['is_safe' => ['html']]),
        ];
    }
}
