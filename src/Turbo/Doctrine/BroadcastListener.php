<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Doctrine;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\Turbo\Attribute\Broadcast;
use Symfony\UX\Turbo\Broadcaster\BroadcasterInterface;

/**
 * Detects changes made from Doctrine entities and broadcasts updates to the Mercure hub.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 *
 * @experimental
 */
final class BroadcastListener implements ResetInterface
{
    private $broadcaster;

    /**
     * @var array<class-string, \ReflectionAttribute[]>
     */
    private $broadcastedClasses;

    /**
     * @var \SplObjectStorage<object, object>
     */
    private $createdEntities;
    /**
     * @var \SplObjectStorage<object, object>
     */
    private $updatedEntities;
    /**
     * @var \SplObjectStorage<object, object>
     */
    private $removedEntities;

    public function __construct(BroadcasterInterface $broadcaster)
    {
        if (80000 > \PHP_VERSION_ID) {
            throw new \LogicException('The broadcast feature requires PHP 8.0 or greater, you must either upgrade to PHP 8 or disable it.');
        }

        $this->reset();

        $this->broadcaster = $broadcaster;
    }

    /**
     * Collects created, updated and removed entities.
     */
    public function onFlush(EventArgs $eventArgs): void
    {
        if (!$eventArgs instanceof OnFlushEventArgs) {
            return;
        }

        $uow = $eventArgs->getEntityManager()->getUnitOfWork();
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->storeEntitiesToPublish($entity, 'createdEntities');
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->storeEntitiesToPublish($entity, 'updatedEntities');
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->storeEntitiesToPublish($entity, 'removedEntities');
        }
    }

    /**
     * Publishes updates for changes collected on flush, and resets the store.
     */
    public function postFlush(): void
    {
        try {
            foreach ($this->createdEntities as $entity) {
                $this->broadcaster->broadcast($entity, Broadcast::ACTION_CREATE);
            }

            foreach ($this->updatedEntities as $entity) {
                $this->broadcaster->broadcast($entity, Broadcast::ACTION_UPDATE);
            }

            foreach ($this->removedEntities as $entity) {
                $this->broadcaster->broadcast($entity, Broadcast::ACTION_REMOVE);
            }
        } finally {
            $this->reset();
        }
    }

    public function reset(): void
    {
        $this->createdEntities = new \SplObjectStorage();
        $this->updatedEntities = new \SplObjectStorage();
        $this->removedEntities = new \SplObjectStorage();
    }

    private function storeEntitiesToPublish(object $entity, string $property): void
    {
        $class = \get_class($entity);

        $this->broadcastedClasses[$class] ?? $this->broadcastedClasses[$class] = (new \ReflectionClass($class))->getAttributes(Broadcast::class);

        if ($this->broadcastedClasses[$class]) {
            $this->{$property}->attach('removedEntities' === $property ? clone $entity : $entity);
        }
    }
}
