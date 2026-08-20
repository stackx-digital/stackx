<?php

namespace App\Services\Embedding;

use RuntimeException;

/**
 * Raised when embedding generation fails (no key, API error, bad shape).
 * Callers degrade gracefully — search still works on whatever is already
 * indexed.
 */
class EmbeddingException extends RuntimeException {}
