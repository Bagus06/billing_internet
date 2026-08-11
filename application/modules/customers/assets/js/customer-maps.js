(function () {
    function coordinateValid(lat, lng) {
        return Number.isFinite(lat) && lat >= -90 && lat <= 90 && Number.isFinite(lng) && lng >= -180 && lng <= 180;
    }

    function setStatus(element, text, danger) {
        if (!element) return;
        element.textContent = text;
        element.classList.toggle('is-error', !!danger);
    }

    window.initCustomerMaps = function () {
        if (!window.google || !google.maps) {
            document.querySelectorAll('[data-customer-map-status]').forEach(function (status) {
                setStatus(status, window.customerMapsConfigurationError || 'Google Maps gagal dimuat. Periksa API key dan koneksi internet.', true);
            });
            return;
        }

        const defaultCenter = { lat: -6.5888, lng: 110.6684 };
        const mapOptions = {
            mapTypeId: google.maps.MapTypeId.HYBRID,
            zoom: 18,
            maxZoom: 22,
            streetViewControl: true,
            fullscreenControl: true,
            mapTypeControl: false,
            gestureHandling: 'greedy'
        };

        const formMapElement = document.querySelector('[data-customer-map]');
        if (formMapElement) {
            const latitudeInput = document.querySelector('[data-customer-latitude]');
            const longitudeInput = document.querySelector('[data-customer-longitude]');
            const status = document.querySelector('[data-customer-map-status]');
            const addressInput = document.querySelector('textarea[name="address"]');
            const initialLat = Number(latitudeInput.value);
            const initialLng = Number(longitudeInput.value);
            const hasInitial = latitudeInput.value !== '' && longitudeInput.value !== '' && coordinateValid(initialLat, initialLng);
            const center = hasInitial ? { lat: initialLat, lng: initialLng } : defaultCenter;
            const map = new google.maps.Map(formMapElement, Object.assign({}, mapOptions, { center: center, zoom: hasInitial ? 20 : 17 }));
            let marker = null;

            function setPoint(lat, lng, moveMap) {
                lat = Number(lat); lng = Number(lng);
                if (!coordinateValid(lat, lng)) return;
                const position = { lat: lat, lng: lng };
                if (!marker) {
                    marker = new google.maps.Marker({ map: map, position: position, draggable: true, title: 'Lokasi pelanggan', animation: google.maps.Animation.DROP });
                    marker.addListener('dragend', function () {
                        const point = marker.getPosition(); setPoint(point.lat(), point.lng(), false);
                    });
                } else marker.setPosition(position);
                latitudeInput.value = lat.toFixed(7);
                longitudeInput.value = lng.toFixed(7);
                setStatus(status, 'Koordinat dipilih: ' + latitudeInput.value + ', ' + longitudeInput.value, false);
                if (moveMap) { map.panTo(position); map.setZoom(20); }
            }

            if (hasInitial) setPoint(initialLat, initialLng, false);
            map.addListener('click', function (event) { setPoint(event.latLng.lat(), event.latLng.lng(), false); });

            const searchInput = document.querySelector('[data-customer-map-search]');
            if (searchInput && google.maps.places) {
                const autocomplete = new google.maps.places.Autocomplete(searchInput, { fields: ['geometry', 'formatted_address', 'name'], componentRestrictions: { country: 'id' } });
                autocomplete.bindTo('bounds', map);
                autocomplete.addListener('place_changed', function () {
                    const place = autocomplete.getPlace();
                    if (!place.geometry || !place.geometry.location) { setStatus(status, 'Lokasi tidak ditemukan. Pilih hasil pencarian yang tersedia.', true); return; }
                    setPoint(place.geometry.location.lat(), place.geometry.location.lng(), true);
                    if (addressInput && !addressInput.value.trim() && place.formatted_address) addressInput.value = place.formatted_address;
                });
            }

            const locationButton = document.querySelector('[data-map-current-location]');
            if (locationButton) locationButton.addEventListener('click', function () {
                if (!navigator.geolocation) { setStatus(status, 'Browser tidak mendukung deteksi lokasi.', true); return; }
                locationButton.disabled = true; setStatus(status, 'Mengambil lokasi perangkat...', false);
                navigator.geolocation.getCurrentPosition(function (position) {
                    setPoint(position.coords.latitude, position.coords.longitude, true); locationButton.disabled = false;
                }, function () {
                    setStatus(status, 'Lokasi tidak dapat diambil. Izinkan akses lokasi atau pilih titik manual.', true); locationButton.disabled = false;
                }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 30000 });
            });

            const clearButton = document.querySelector('[data-map-clear]');
            if (clearButton) clearButton.addEventListener('click', function () {
                latitudeInput.value = ''; longitudeInput.value = '';
                if (marker) { marker.setMap(null); marker = null; }
                setStatus(status, 'Titik koordinat dihapus. Klik peta untuk memilih ulang.', false);
            });
            const streetViewButton = document.querySelector('[data-map-street-view]');
            if (streetViewButton) streetViewButton.addEventListener('click', function () {
                const lat = Number(latitudeInput.value), lng = Number(longitudeInput.value);
                if (!coordinateValid(lat, lng)) { setStatus(status, 'Pilih titik pelanggan sebelum membuka Street View.', true); return; }
                const panorama = map.getStreetView();
                panorama.setPosition({ lat: lat, lng: lng }); panorama.setPov({ heading: 0, pitch: 0 }); panorama.setVisible(true);
            });
        }

        const detailElement = document.querySelector('[data-customer-detail-map]');
        const detailWrap = document.querySelector('[data-customer-detail-map-wrap]');
        if (detailElement && detailWrap) {
            let detailMap = null, detailMarker = null;
            function showLocation(card) {
                const lat = Number(card.dataset.latitude), lng = Number(card.dataset.longitude);
                const hasPoint = card.dataset.latitude !== '' && card.dataset.longitude !== '' && coordinateValid(lat, lng);
                detailWrap.hidden = !hasPoint;
                if (!hasPoint) return;
                const position = { lat: lat, lng: lng };
                const coordinate = detailWrap.querySelector('[data-customer-detail-coordinate]');
                const link = detailWrap.querySelector('[data-customer-map-link]');
                if (coordinate) coordinate.textContent = lat.toFixed(7) + ', ' + lng.toFixed(7);
                if (link) link.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(lat + ',' + lng);
                if (!detailMap) detailMap = new google.maps.Map(detailElement, Object.assign({}, mapOptions, { center: position, zoom: 20, gestureHandling: 'cooperative' }));
                else { detailMap.setCenter(position); detailMap.setZoom(20); }
                if (!detailMarker) detailMarker = new google.maps.Marker({ map: detailMap, position: position, title: card.dataset.name || 'Lokasi pelanggan' });
                else detailMarker.setPosition(position);
                setTimeout(function () { google.maps.event.trigger(detailMap, 'resize'); detailMap.setCenter(position); }, 100);
            }
            document.querySelectorAll('[data-customer-detail]').forEach(function (card) {
                card.addEventListener('click', function (event) { if (!event.target.closest('a, button, summary, details')) showLocation(card); });
                card.addEventListener('keydown', function (event) { if (event.key === 'Enter' || event.key === ' ') showLocation(card); });
            });
        }
    };

    window.gm_authFailure = function () {
        document.querySelectorAll('[data-customer-map-status]').forEach(function (status) {
            setStatus(status, 'Google Maps API belum aktif atau domain ini belum diizinkan.', true);
        });
    };
})();
