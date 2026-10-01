/**
 * Alpine component `sortable` — drag-and-drop (plus keyboard "move up/down") reordering
 * that PATCHes the new id order to a reorder endpoint as {"ids": [...]}.
 *
 * Markup is produced by the Blade components x-admin.sortable (container) and
 * x-admin.sort-handle (per-item controls). Items are any elements carrying
 * `data-sortable-id` + `draggable="true"` inside the container (table rows, list items, grid cards).
 *
 * @param {{ url: string, axis?: 'x'|'y' }} options  axis "x" for grids (compare horizontally), "y" for lists/tables
 */
export default function sortable({ url, axis = 'y' }) {
    return {
        dragging: null,
        message: '',
        failed: false,

        /** Items in their current DOM order. */
        items() {
            return [...this.$root.querySelectorAll('[data-sortable-id]')];
        },

        start(event) {
            const item = event.target.closest?.('[data-sortable-id]');
            if (!item || !this.$root.contains(item)) return;

            this.dragging = item;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', item.dataset.sortableId);
            item.classList.add('opacity-50');
        },

        over(event) {
            if (!this.dragging) return;
            event.preventDefault();

            const target = event.target.closest?.('[data-sortable-id]');
            if (!target || target === this.dragging || target.parentNode !== this.dragging.parentNode) return;

            const rect = target.getBoundingClientRect();
            const after = axis === 'x'
                ? event.clientX > rect.left + rect.width / 2
                : event.clientY > rect.top + rect.height / 2;

            target.parentNode.insertBefore(this.dragging, after ? target.nextSibling : target);
        },

        end() {
            if (!this.dragging) return;

            this.dragging.classList.remove('opacity-50');
            this.dragging = null;
            this.save();
        },

        /**
         * Keyboard alternative: move the item containing `button` one step (-1 up / +1 down) and keep focus on it.
         */
        move(button, step) {
            const item = button.closest('[data-sortable-id]');
            const sibling = step < 0 ? item?.previousElementSibling : item?.nextElementSibling;
            if (!item || !sibling?.matches('[data-sortable-id]')) return;

            item.parentNode.insertBefore(item, step < 0 ? sibling : sibling.nextSibling);
            button.focus();
            this.save();
        },

        async save() {
            const ids = this.items().map((el) => Number(el.dataset.sortableId));
            this.failed = false;
            this.message = 'Saving order…';

            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ ids }),
                });

                if (!response.ok) throw new Error(String(response.status));

                this.message = (await response.json()).message ?? 'Order saved.';
            } catch {
                this.failed = true;
                this.message = 'Could not save the new order. Reload the page and try again.';
            }
        },
    };
}
