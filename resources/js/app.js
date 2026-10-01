import './bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import sortable from './sortable';
import bookingForm from './booking';

Alpine.plugin(focus);
Alpine.data('sortable', sortable);
Alpine.data('bookingForm', bookingForm);
window.Alpine = Alpine;
Alpine.start();
