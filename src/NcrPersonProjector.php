<?php
declare(strict_types=1);

namespace Triniti\People;

use Gdbots\Ncr\AbstractNodeProjector;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\EventSubscriberTrait;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\People\Mixin\PersonCreated\PersonCreated;
use Triniti\Schemas\People\Mixin\PersonCreated\PersonCreatedV1Mixin;
use Triniti\Schemas\People\Mixin\PersonDeleted\PersonDeleted;
use Triniti\Schemas\People\Mixin\PersonRenamed\PersonRenamed;
use Triniti\Schemas\People\Mixin\PersonUpdated\PersonUpdated;

class NcrPersonProjector extends AbstractNodeProjector implements EventSubscriber
{
    use EventSubscriberTrait;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $curie = PersonCreatedV1Mixin::findOne()->getCurie();
        return [
            "{$curie->getVendor()}:{$curie->getPackage()}:{$curie->getCategory()}:*" => 'onEvent',
        ];
    }

    /**
     * @param PersonCreated $event
     * @param Pbjx          $pbjx
     */
    public function onPersonCreated(PersonCreated $event, Pbjx $pbjx): void
    {
        $this->handleNodeCreated($event, $pbjx);
    }

    /**
     * @param PersonDeleted $event
     * @param Pbjx          $pbjx
     */
    public function onPersonDeleted(PersonDeleted $event, Pbjx $pbjx): void
    {
        $this->handleNodeDeleted($event, $pbjx);
    }

    /**
     * @param PersonRenamed $event
     * @param Pbjx          $pbjx
     */
    public function onPersonRenamed(PersonRenamed $event, Pbjx $pbjx): void
    {
        $this->handleNodeRenamed($event, $pbjx);
    }

    /**
     * @param PersonUpdated $event
     * @param Pbjx          $pbjx
     */
    public function onPersonUpdated(PersonUpdated $event, Pbjx $pbjx): void
    {
        $this->handleNodeUpdated($event, $pbjx);
    }
}
