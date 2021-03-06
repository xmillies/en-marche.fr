<?php

namespace App\Donation;

use App\Geocoder\GeocodableEntityEventInterface;
use App\Geocoder\GeocodableInterface;

final class DonationWasCreatedEvent extends DonationEvent implements GeocodableEntityEventInterface
{
    public function getGeocodableEntity(): GeocodableInterface
    {
        return $this->getDonation();
    }
}
