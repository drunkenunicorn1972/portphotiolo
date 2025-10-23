/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */

// start importing our css
import './styles/app.scss';

// Start the Stimulus application (if using)
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

// Make it globally available if needed
window.L = L;

// start stimulus
import './bootstrap.js';

// Notify the console that the app is ready to go!
console.log('App initialized!');
