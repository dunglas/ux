<?php

/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Broadcaster;

/**
 * Broadcast an update of an entity.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
interface BroadcasterInterface
{
    public function broadcast(object $entity, string $action): void;
}