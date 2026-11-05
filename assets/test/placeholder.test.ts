/*
 * This file is part of the Dowsing Machine project.
 *
 * (c) Dowsing Machine contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

describe('test infrastructure', () => {
    it('creates a DOM element', () => {
        const div = document.createElement('div');
        div.textContent = 'Hello Dowsing Machine';
        document.body.appendChild(div);

        expect(div).toBeInTheDocument();
        expect(div).toHaveTextContent('Hello Dowsing Machine');
    });
});
