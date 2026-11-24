(() => {
    let mapsPromise;
    function loadMaps(key) {
        if (window.google?.maps?.Map) return Promise.resolve();
        if (!mapsPromise) {
            mapsPromise = new Promise((resolve, reject) => {
                window.membershipMapReady = resolve;
                window.gm_authFailure = () => reject(new Error('Map authorization failed'));
                const script = document.createElement('script');
                script.src = 'https://maps.googleapis.com/maps/api/js?' + new URLSearchParams({key, loading: 'async', callback: 'membershipMapReady', v: 'weekly'});
                script.async = true;
                script.onerror = reject;
                document.head.append(script);
            });
        }
        return mapsPromise;
    }
    document.querySelectorAll('[data-location]').forEach(panel => {
        const latitude = panel.querySelector('[data-latitude]');
        const longitude = panel.querySelector('[data-longitude]');
        const consent = panel.querySelector('[data-location-consent]');
        const weather = panel.querySelector('[data-weather]');
        const mapMessage = panel.querySelector('[data-map-message]');
        const editable = panel.dataset.editable === 'true';
        const weatherButton = panel.querySelector('[data-load-weather]');
        const locationStatus = panel.querySelector('[data-location-status]');
        let map, point, request;
        function coordinates() {
            if (latitude.value.trim() === '' || longitude.value.trim() === '') return null;
            const lat = Number(latitude.value), lng = Number(longitude.value);
            return Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180 ? {lat, lng} : null;
        }
        function drawPoint() {
            if (!map) return;
            const position = coordinates();
            if (point) point.setMap(null);
            if (position) {
                point = new google.maps.Circle({map, center: position, radius: 45, fillColor: '#ad2533', fillOpacity: 0.9, strokeColor: '#ffffff', strokeWeight: 2});
                map.panTo(position);
            }
        }
        async function loadWeather() {
            if (request) request.abort();
            const position = coordinates();
            if (!position) { weather.textContent = 'Choose a location or enter valid latitude and longitude first.'; return; }
            request = new AbortController();
            weather.textContent = 'Loading current weather…';
            try {
                const url = new URL(panel.dataset.weatherUrl, window.location.origin);
                url.search = new URLSearchParams({lat: position.lat, lon: position.lng});
                const response = await fetch(url, {signal: request.signal, headers: {Accept: 'application/json'}, credentials: 'same-origin'});
                const data = await response.json();
                if (!response.ok) { weather.textContent = response.status === 429 ? 'Too many weather requests. Please wait a minute.' : (data.message || 'Weather is currently unavailable.'); return; }
                weather.textContent = `${data.place}: ${data.temperature}°C · ${data.description}${data.humidity == null ? '' : ` · Humidity ${data.humidity}%`}`;
            } catch (error) {
                if (error.name !== 'AbortError') weather.textContent = 'Weather is currently unavailable. Please try again later.';
            }
        }
        function updateLocationStatus() {
            const selected = coordinates() !== null;
            locationStatus.textContent = selected ? 'Location selected' : 'No location selected';
            locationStatus.dataset.selected = String(selected);
            weatherButton.disabled = !selected;
        }
        function locationChanged() {
            if (request) request.abort();
            updateLocationStatus();
            if (consent) consent.checked = false;
            weather.textContent = 'Location changed. Load weather to see current conditions.';
            drawPoint();
        }
        latitude.addEventListener('input', locationChanged);
        longitude.addEventListener('input', locationChanged);
        weatherButton.addEventListener('click', loadWeather);
        updateLocationStatus();
        panel.querySelector('[data-clear-location]')?.addEventListener('click', () => { latitude.value = ''; longitude.value = ''; if (request) request.abort(); locationChanged(); });
        panel.querySelector('[data-load-map]').addEventListener('click', async event => {
            const button = event.currentTarget;
            button.disabled = true;
            mapMessage.textContent = 'Loading Google Maps…';
            try {
                await loadMaps(panel.dataset.mapsKey);
                const canvas = panel.querySelector('[data-map]');
                canvas.hidden = false;
                map = new google.maps.Map(canvas, {center: coordinates() || {lat: 14.5995, lng: 120.9842}, zoom: 14, streetViewControl: false, mapTypeControl: false});
                drawPoint();
                panel.querySelector('[data-map-placeholder]').hidden = true;
                if (editable) map.addListener('click', event => {
                    latitude.value = event.latLng.lat().toFixed(7);
                    longitude.value = event.latLng.lng().toFixed(7);
                    locationChanged();
                });
                mapMessage.textContent = editable ? 'Click the map to choose a location. Manila is only the starting view; no location is saved until selected.' : 'Showing the saved location.';
                button.hidden = true;
            } catch {
                button.disabled = false;
                mapMessage.textContent = 'Google Maps is unavailable. You can still use the coordinate fields.';
            }
        });
    });
})();
