import './bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import sortable from './sortable';

Alpine.plugin(focus);
Alpine.data('sortable', sortable);
window.Alpine = Alpine;
Alpine.start();
