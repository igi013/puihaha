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
    function createLocationPin(map, position) {
        class LocationPin extends google.maps.OverlayView {
            onAdd() {
                this.element = document.createElement('div');
                this.element.setAttribute('role', 'img');
                this.element.setAttribute('aria-label', 'Selected membership location');
                this.element.style.cssText = 'position:absolute;display:flex;flex-direction:column;align-items:center;transform:translate(-50%,-100%);pointer-events:none;';
                const pin = document.createElement('div');
                pin.style.cssText = 'width:44px;height:56px;filter:drop-shadow(0 3px 3px #0006);';
                pin.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="44" height="56" viewBox="0 0 44 56" aria-hidden="true"><path d="M22 53C18 47 3 33 3 22a19 19 0 1 1 38 0c0 11-15 25-19 31Z" fill="#ad2533" stroke="#fff" stroke-width="3"/><circle cx="22" cy="22" r="7" fill="#fff"/></svg>';
                this.element.append(pin);
                this.getPanes().floatPane.append(this.element);
            }
            draw() {
                const pixel = this.getProjection().fromLatLngToDivPixel(new google.maps.LatLng(position));
                if (!pixel || !this.element) return;
                this.element.style.left = `${pixel.x}px`;
                this.element.style.top = `${pixel.y}px`;
            }
            onRemove() {
                this.element?.remove();
            }
        }
        const pin = new LocationPin();
        pin.setMap(map);
        return pin;
    }
    document.querySelectorAll('[data-location]').forEach(panel => {
        const latitude = panel.querySelector('[data-latitude]');
        const longitude = panel.querySelector('[data-longitude]');
        const consent = panel.querySelector('[data-location-consent]');
        const weather = panel.querySelector('[data-weather]');
        const mapMessage = panel.querySelector('[data-map-message]');
        const editable = panel.dataset.editable === 'true';
        const weatherButton = panel.querySelector('[data-load-weather]');
        const weatherReading = panel.querySelector('[data-weather-reading]');
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
                point = createLocationPin(map, position);
                map.panTo(position);
            }
        }
        async function loadWeather() {
            if (request) request.abort();
            const position = coordinates();
            if (!position) { weather.textContent = 'Choose a location or enter valid latitude and longitude first.'; return; }
            request = new AbortController();
            const activeRequest = request;
            weatherButton.disabled = true;
            weatherButton.textContent = 'Loading…';
            weather.dataset.state = '';
            weather.textContent = 'Loading current weather…';
            try {
                const url = new URL(panel.dataset.weatherUrl, window.location.origin);
                url.search = new URLSearchParams({lat: position.lat, lon: position.lng});
                const response = await fetch(url, {signal: request.signal, headers: {Accept: 'application/json'}, credentials: 'same-origin'});
                const data = await response.json();
                if (!response.ok) { weatherReading.hidden = true; weather.textContent = response.status === 429 ? 'Too many weather requests. Please wait a minute.' : (data.message || 'Weather is currently unavailable.'); return; }
                if (!Number.isFinite(data.temperature)) throw new Error('Invalid weather response');
                panel.querySelector('[data-weather-temperature]').textContent = new Intl.NumberFormat(undefined, {maximumFractionDigits: 2}).format(data.temperature);
                panel.querySelector('[data-weather-place]').textContent = data.place;
                panel.querySelector('[data-weather-description]').textContent = data.description;
                panel.querySelector('[data-weather-humidity]').textContent = data.humidity == null ? '' : `Humidity ${data.humidity}%`;
                const description = String(data.description).toLowerCase();
                const icon = /thunder/.test(description) ? 'storm' : /snow|sleet/.test(description) ? 'snow' : /rain|drizzle/.test(description) ? 'rain' : /mist|fog|haze|smoke|dust|sand|ash/.test(description) ? 'mist' : /clear/.test(description) ? 'sun' : 'cloud';
                panel.querySelectorAll('[data-weather-icon]').forEach(shape => {
                    shape.toggleAttribute('hidden', shape.dataset.weatherIcon !== icon && !(shape.dataset.weatherIcon === 'cloud' && icon !== 'sun'));
                });
                weatherReading.hidden = false;
                weather.dataset.state = 'ready';
                weather.textContent = `${data.place}: ${data.temperature}°C · ${data.description}${data.humidity == null ? '' : ` · Humidity ${data.humidity}%`}`;
            } catch (error) {
                if (error.name !== 'AbortError') {
                    weatherReading.hidden = true;
                    weather.textContent = 'Weather is currently unavailable. Please try again later.';
                }
            } finally {
                if (request === activeRequest) {
                    weatherButton.disabled = !coordinates();
                    weatherButton.textContent = weatherReading.hidden ? 'Load weather' : 'Refresh weather';
                }
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
            weatherReading.hidden = true;
            weather.dataset.state = '';
            weatherButton.textContent = 'Load weather';
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
