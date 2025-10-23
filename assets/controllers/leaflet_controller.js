import { Controller } from '@hotwired/stimulus';
import L from 'leaflet';

// Fix default marker icon issue with webpack
import icon from 'leaflet/dist/images/marker-icon.png';
import iconShadow from 'leaflet/dist/images/marker-shadow.png';

let DefaultIcon = L.icon({
    iconUrl: icon,
    shadowUrl: iconShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41]
});

L.Marker.prototype.options.icon = DefaultIcon;

export default class extends Controller {
    static values = {
        latitude: Number,
        longitude: Number,
        zoom: { type: Number, default: 13 }
    }

    connect() {
        // Ensure Leaflet is loaded
        if (typeof L === 'undefined') {
            console.error('Leaflet is not loaded');
            return;
        }

        // Initialize the map
        const map = L.map(this.element).setView(
            [this.latitudeValue, this.longitudeValue],
            this.zoomValue
        );

        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Add marker
        L.marker([this.latitudeValue, this.longitudeValue]).addTo(map);
    }
}
