document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Sticky Header
    const header = document.querySelector('.main-header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // 2. Mobile Menu
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const closeMenuBtn = document.getElementById('closeMenu');
    const mobileNav = document.getElementById('mobileNav');
    const overlay = document.getElementById('overlay');
    const mobileLinks = document.querySelectorAll('.mobile-link');
    
    const openMenu = () => {
        mobileNav.classList.add('active');
        overlay.classList.add('active');
    };
    const closeMenu = () => {
        mobileNav.classList.remove('active');
        overlay.classList.remove('active');
    };

    mobileMenuBtn.addEventListener('click', openMenu);
    closeMenuBtn.addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);
    mobileLinks.forEach(link => link.addEventListener('click', closeMenu));
    
    // 3. Particles.js Initialization
    particlesJS('particles-js', {
        particles: {
            number: { value: 60, density: { enable: true, value_area: 800 } },
            color: { value: "#ffffff" },
            shape: { type: "circle" },
            opacity: { value: 0.3, random: true },
            size: { value: 3, random: true },
            line_linked: { enable: false },
            move: { enable: true, speed: 1, direction: "none", random: true, straight: false, out_mode: "out" }
        },
        retina_detect: true
    });

    // 4. Locations and Maps
    const locationsContainer = document.getElementById('locations-container');
    if(locationsContainer){
        // Initialize main map (centered on Egypt)
        const mainMap = L.map('main-map').setView([26.8206, 30.8025], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(mainMap);

        // Fetch locations data from server
        fetch('api/get_locations.php') // You need to create this API endpoint
            .then(response => response.json())
            .then(locations => {
                if (locations.length === 0) {
                     locationsContainer.innerHTML = '<p>لا توجد أماكن متاحة حالياً.</p>';
                     return;
                }
                locationsContainer.innerHTML = ''; // Clear loader
                
                locations.forEach(location => {
                    // Create Location Card
                    const card = document.createElement('div');
                    card.className = 'location-card';
                    card.innerHTML = `
                        <div class="location-map" id="map-${location.id}"></div>
                        <div class="location-info">
                            <h3>${location.name}</h3>
                            <p><i class="fas fa-map-marker-alt"></i> ${location.address}</p>
                            <p><i class="fas fa-clock"></i> ${location.working_hours}</p>
                            <p><i class="fas fa-phone"></i> ${location.phone}</p>
                            <a href="https://www.google.com/maps?q=${location.latitude},${location.longitude}" target="_blank" class="btn btn-primary" style="margin-top: auto;">عرض على الخريطة</a>
                        </div>
                    `;
                    locationsContainer.appendChild(card);
                    
                    // Create mini map inside the card
                    const miniMap = L.map(`map-${location.id}`, {
                        zoomControl: false, dragging: false, scrollWheelZoom: false, doubleClickZoom: false
                    }).setView([location.latitude, location.longitude], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(miniMap);
                    L.marker([location.latitude, location.longitude]).addTo(miniMap);

                    // Add marker to the main map
                    const mainMarker = L.marker([location.latitude, location.longitude]).addTo(mainMap);
                    mainMarker.bindPopup(`<b>${location.name}</b><br>${location.address}`);
                });
            })
            .catch(error => {
                locationsContainer.innerHTML = '<p>حدث خطأ أثناء تحميل أماكن التواجد.</p>';
                console.error('Error fetching locations:', error);
            });
    }
});