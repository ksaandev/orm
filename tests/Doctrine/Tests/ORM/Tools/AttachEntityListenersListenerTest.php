<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Tools\AttachEntityListenersListener;
use Doctrine\Tests\OrmTestCase;
use function func_get_args;

class AttachEntityListenersListenerTest extends OrmTestCase
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var AttachEntityListenersListener */
    private $listener;

    /** @var ClassMetadataFactory */
    private $factory;

    public function setUp() : void
    {
        $this->listener = new AttachEntityListenersListener();
        $driver         = $this->createAnnotationDriver();
        $this->em       = $this->getTestEntityManager();
        $evm            = $this->em->getEventManager();
        $this->factory  = new ClassMetadataFactory();

        $evm->addEventListener(Events::loadClassMetadata, $this->listener);
        $this->em->getConfiguration()->setMetadataDriverImpl($driver);
        $this->factory->setEntityManager($this->em);
    }

    public function testAttachEntityListeners() : void
    {
        $this->listener->addEntityListener(
            AttachEntityListenersListenerTestFooEntity::class,
            AttachEntityListenersListenerTestListener::class,
            Events::postLoad,
            'postLoadHandler'
        );

        $metadata = $this->factory->getMetadataFor(AttachEntityListenersListenerTestFooEntity::class);

        self::assertArrayHasKey('postLoad', $metadata->entityListeners);
        self::assertCount(1, $metadata->entityListeners['postLoad']);
        self::assertEquals('postLoadHandler', $metadata->entityListeners['postLoad'][0]['method']);
        self::assertEquals(AttachEntityListenersListenerTestListener::class, $metadata->entityListeners['postLoad'][0]['class']);
    }

    public function testAttachToExistingEntityListeners() : void
    {
        $this->listener->addEntityListener(
            AttachEntityListenersListenerTestBarEntity::class,
            AttachEntityListenersListenerTestListener2::class,
            Events::prePersist,
            'prePersist'
        );

        $this->listener->addEntityListener(
            AttachEntityListenersListenerTestBarEntity::class,
            AttachEntityListenersListenerTestListener2::class,
            Events::postPersist,
            'postPersistHandler'
        );

        $metadata = $this->factory->getMetadataFor(AttachEntityListenersListenerTestBarEntity::class);

        self::assertArrayHasKey('postPersist', $metadata->entityListeners);
        self::assertArrayHasKey('prePersist', $metadata->entityListeners);

        self::assertCount(2, $metadata->entityListeners['prePersist']);
        self::assertCount(2, $metadata->entityListeners['postPersist']);

        self::assertEquals('prePersist', $metadata->entityListeners['prePersist'][0]['method']);
        self::assertEquals(AttachEntityListenersListenerTestListener::class, $metadata->entityListeners['prePersist'][0]['class']);

        self::assertEquals('prePersist', $metadata->entityListeners['prePersist'][1]['method']);
        self::assertEquals(AttachEntityListenersListenerTestListener2::class, $metadata->entityListeners['prePersist'][1]['class']);

        self::assertEquals('postPersist', $metadata->entityListeners['postPersist'][0]['method']);
        self::assertEquals(AttachEntityListenersListenerTestListener::class, $metadata->entityListeners['postPersist'][0]['class']);

        self::assertEquals('postPersistHandler', $metadata->entityListeners['postPersist'][1]['method']);
        self::assertEquals(AttachEntityListenersListenerTestListener2::class, $metadata->entityListeners['postPersist'][1]['class']);
    }

    public function testDoNotDuplicateEntityListener() : void
    {
        $this->listener->addEntityListener(
            AttachEntityListenersListenerTestFooEntity::class,
            AttachEntityListenersListenerTestListener::class,
            Events::postPersist,
            'postPersist'
        );

        $this->listener->addEntityListener(
            AttachEntityListenersListenerTestFooEntity::class,
            AttachEntityListenersListenerTestListener::class,
            Events::postPersist,
            'postPersist'
        );

        $class = $this->factory->getMetadataFor(AttachEntityListenersListenerTestFooEntity::class);

        self::assertCount(1, $class->entityListeners[Events::postPersist]);
    }
}

/**
 * @ORM\Entity
 */
class AttachEntityListenersListenerTestFooEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
}

/**
 * @ORM\Entity
 * @ORM\EntityListeners({AttachEntityListenersListenerTestListener::class})
 */
class AttachEntityListenersListenerTestBarEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
}

class AttachEntityListenersListenerTestListener
{
    public $calls;

    /** @ORM\PrePersist */
    public function prePersist()
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    /** @ORM\PostLoad */
    public function postLoadHandler()
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    /** @ORM\PostPersist */
    public function postPersist()
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }
}

class AttachEntityListenersListenerTestListener2
{
    public $calls;

    /** @ORM\PrePersist */
    public function prePersist()
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    /** @ORM\PostPersist */
    public function postPersistHandler()
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }
}
