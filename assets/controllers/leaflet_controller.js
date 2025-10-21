import { Controller } from '@hotwired/stimulus';

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
