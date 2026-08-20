<?php

namespace App\Services\AdLibrary;

use RuntimeException;

/**
 * Thrown when a live Ad Library sync is attempted while the feature is off (no
 * flag / no token). Callers degrade to saved/demo data.
 */
class AdLibraryDisabledException extends RuntimeException {}
