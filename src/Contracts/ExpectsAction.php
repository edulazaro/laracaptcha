<?php

namespace EduLazaro\Laracaptcha\Contracts;

/**
 * A driver whose tokens are bound to the action they were minted for.
 *
 * reCAPTCHA v3 stamps every token with the action the widget asked for, so a
 * token minted on a low value form can otherwise be replayed into a critical
 * one. A driver implementing this lets the caller say which action it expects,
 * and refuses anything else.
 *
 * Implementations must not mutate: the manager caches driver instances for the
 * whole request, so returning a clone is the only safe option.
 */
interface ExpectsAction
{
    /**
     * A copy of this driver that only accepts tokens minted for $action.
     *
     * Null means no check, which is the default and the pre-existing
     * behaviour.
     */
    public function expectingAction(?string $action): static;
}
