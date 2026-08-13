<?php

namespace App\Services\Ai\Contracts;

/**
 * Provider-agnostic AI contract. Drivers (Anthropic, OpenAI, …) implement this
 * so the rest of the app never depends on a specific vendor. All AI features
 * (tagging in M5, ad-variation generation in P4) go through this interface.
 */
interface AiProvider
{
    /**
     * Send a system + user prompt and return a decoded JSON object. The
     * provider is instructed to reply with JSON only; callers are responsible
     * for validating the shape (e.g. Zod-equivalent validation server-side).
     *
     * @param  array<string, mixed>  $options  Per-call overrides (model, max_tokens, temperature).
     * @return array<string, mixed>
     *
     * @throws \App\Services\Ai\Exceptions\AiException
     */
    public function structuredJson(string $system, string $user, array $options = []): array;

    /** The driver's canonical name (e.g. "anthropic"). */
    public function name(): string;

    /** The model this driver is configured to use. */
    public function model(): string;
}
