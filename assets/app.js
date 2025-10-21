/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.scss';
// Start the Stimulus application (if using)
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

// Make it globally available if needed
window.L = L;

import './bootstrap';

console.log('App initialized!');
