
import { Controller } from '@hotwired/stimulus';
import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay, Keyboard, Mousewheel } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

export default class extends Controller {
    connect() {
        this.swiper = new Swiper(this.element, {
            modules: [Navigation, Pagination, Autoplay, Keyboard, Mousewheel],

            // Display multiple slides at once
            slidesPerView: 'auto',
            spaceBetween: 20,
            centeredSlides: true,
            loop: true,

            // Auto-play
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            },

            // Speed
            speed: 800,

            // Smooth momentum
            freeMode: false,

            // Navigation arrows
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },

            // Pagination dots
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
                dynamicBullets: true,
            },

            // Responsive breakpoints
            breakpoints: {
                // Mobile
                320: {
                    spaceBetween: 12,
                },
                // Tablet
                768: {
                    spaceBetween: 16,
                },
                // Desktop
                1024: {
                    spaceBetween: 24,
                }
            },

            // Keyboard control
            keyboard: {
                enabled: true,
            },

            // Mouse wheel control
            mousewheel: {
                forceToAxis: true,
            },
        });
    }

    disconnect() {
        if (this.swiper) {
            this.swiper.destroy();
            this.swiper = null;
        }
    }
}
