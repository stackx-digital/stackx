<?php

namespace App\Services\Marketing;

use RuntimeException;

/** Raised when a live Meta Marketing sync can't run or the API rejects it. */
class MetaException extends RuntimeException {}
