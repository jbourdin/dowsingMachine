/*
 * This file is part of the Dowsing Machine project.
 *
 * (c) Dowsing Machine contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { initSortableTable } from './shared/sortable-table';
import './styles/app.scss';

const BUCKETS = ['pokemon', 'supporter', 'item', 'tool', 'stadium', 'energy', 'ace_spec'] as const;

document.addEventListener('DOMContentLoaded', () => {
    BUCKETS.forEach((bucket) => {
        initSortableTable(`sortable-staples-${bucket}`, 'staple');
    });
});
