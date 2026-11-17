@php($editable = $editable ?? true)
@php($location = $location ?? null)
<section class="location-panel full" data-location data-editable="{{ $editable ? 'true' : 'false' }}" data-maps-key="{{ config('services.google_maps.key') }}" data-weather-url="{{ route('weather.show') }}">
    <h2>{{ $editable ? 'Location (optional)' : 'Saved location' }}</h2>
    <p class="muted">{{ $editable ? 'Choose a point on the map or enter both coordinates. Leave them blank if no location should be saved.' : 'The location recorded for this member or application.' }}</p>
    <div class="grid">
        <div><label for="latitude">Latitude</label><input id="latitude" data-latitude {{ $editable ? 'name=latitude' : '' }} type="number" min="-90" max="90" step="0.0000001" value="{{ $editable ? old('latitude', $location?->latitude) : $location?->latitude }}" @readonly(!$editable)></div>
        <div><label for="longitude">Longitude</label><input id="longitude" data-longitude {{ $editable ? 'name=longitude' : '' }} type="number" min="-180" max="180" step="0.0000001" value="{{ $editable ? old('longitude', $location?->longitude) : $location?->longitude }}" @readonly(!$editable)></div>
    </div>
    <div class="actions" style="margin:18px 0"><button type="button" class="secondary" data-load-map @disabled(!config('services.google_maps.key') || (!$editable && $location?->latitude === null))>Show Google map</button><button type="button" class="secondary" data-load-weather @disabled(!$editable && $location?->latitude === null)>Load current weather</button>@if($editable)<button type="button" class="secondary" data-clear-location>Clear location</button>@endif</div>
    <div data-map class="location-map" hidden aria-label="Google map: select a membership location"></div>
    <p class="muted" data-map-message role="status">{{ config('services.google_maps.key') ? 'Open the map when you want to choose or view the location.' : 'Google Maps has not been configured yet. Coordinates can still be entered manually.' }}</p>
    <p data-weather role="status" aria-live="polite">Weather has not been loaded.</p>
    @if($editable)<label class="location-consent"><input data-location-consent type="checkbox" name="location_consent" value="1" @checked(old('location_consent', (bool) $location?->location_consented_at))>The member agrees to store this location. Showing the map shares it with Google; loading weather shares approximate coordinates with OpenWeather.</label>@endif
</section>
@once
<style>.location-panel{margin-top:24px;border-top:1px solid var(--line);padding-top:24px}.location-panel h2{margin-bottom:10px}.location-map{height:340px;border:1px solid var(--line);border-radius:6px;margin-bottom:14px}.location-consent{display:flex;align-items:flex-start;gap:10px;font-weight:400;margin-top:18px}.location-consent input{width:18px;min-height:18px;flex:0 0 18px;margin-top:3px}.location-map button{min-height:0;padding:0;border:0;background:transparent;color:inherit}</style>
@push('scripts')<script src="{{ asset('js/membership-location.js') }}" defer></script>@endpush
@endonce
