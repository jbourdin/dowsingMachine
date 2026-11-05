/*
 * This file is part of the Dowsing Machine project.
 *
 * (c) Dowsing Machine contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { describe, expect, it } from 'vitest';
import { isZoomWorthy } from '../shared/image-zoom';

describe('isZoomWorthy', () => {
    it('zooms when the original is much larger than rendered', () => {
        expect(isZoomWorthy(4096, 540)).toBe(true);
    });

    it('skips images shown near native size', () => {
        expect(isZoomWorthy(600, 540)).toBe(false);
    });

    it('skips hidden images (zero rendered width)', () => {
        expect(isZoomWorthy(4096, 0)).toBe(false);
    });
});
