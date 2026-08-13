<?php

namespace App\Services\Ai\Exceptions;

use RuntimeException;

/**
 * Raised when an AI provider call fails or returns unparseable output. Callers
 * should catch this and degrade gracefully — analytics must keep working even
 * when the AI layer is down (§5).
 */
class AiException extends RuntimeException {}
