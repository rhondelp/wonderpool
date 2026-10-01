<?php

namespace App\Support;

/**
 * Curated heroicon (outline) names offered by the amenity icon picker.
 * To offer another icon, add "heroicon-name" => "Search words" below (names: https://heroicons.com).
 */
final class AmenityIcons
{
    /**
     * Icon name => label / search keywords.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'sun' => 'Sun, pool, outdoor',
            'face-smile' => 'Smile, kids, family',
            'home-modern' => 'Cottage, house, room',
            'home' => 'Home, room',
            'building-library' => 'Function hall, events',
            'building-office-2' => 'Building, rooms',
            'building-storefront' => 'Store, canteen, kiosk',
            'trophy' => 'Trophy, games, billiards',
            'microphone' => 'Videoke, karaoke, microphone',
            'musical-note' => 'Music, sound system',
            'sparkles' => 'Sparkles, clean, special',
            'truck' => 'Parking, vehicle',
            'map-pin' => 'Location, map',
            'wifi' => 'Wi-Fi, internet',
            'tv' => 'TV, entertainment',
            'fire' => 'Grill, barbecue, fire',
            'cake' => 'Cake, party, birthday',
            'gift' => 'Gift, celebration',
            'heart' => 'Heart, wedding, romance',
            'users' => 'Group, guests, people',
            'user-group' => 'Team, gathering',
            'camera' => 'Photo spot, camera',
            'shield-check' => 'Security, safe, lifeguard',
            'lifebuoy' => 'Lifeguard, safety, pool',
            'bolt' => 'Power, generator, electricity',
            'light-bulb' => 'Lights, lighting',
            'moon' => 'Night, overnight',
            'clock' => 'Hours, time',
            'beaker' => 'Drinks, bar',
            'shopping-bag' => 'Shop, supplies',
            'key' => 'Locker, key, room',
            'lock-closed' => 'Lockers, storage',
            'puzzle-piece' => 'Games, activities',
            'globe-asia-australia' => 'Nature, garden, view',
            'cloud' => 'Shade, weather',
            'star' => 'Featured, star',
        ];
    }

    /**
     * Allowed icon names (validation).
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::all());
    }
}
